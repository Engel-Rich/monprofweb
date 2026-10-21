<?php

namespace App\Http\Controllers\Web;

use App\DTO\TransactionVerificationResult;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\RevokeAccessRequest;
use App\Jobs\SendMailJob;
use App\Jobs\VerifyPendingTransaction;
use App\Models\Categorie;
use App\Models\Paiements;
use App\Models\PayementServices;
use App\Models\PaymentProvider;
use App\Services\AccessRevocationService;
use App\Services\Admin\PaymentAdminPresenter;
use App\Services\PaiementService;
use App\Services\Payments\TransactionFinalizationService;
use App\Services\SendMessageService;
use App\Support\TableFilters;
use Illuminate\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class PaiementsController extends Controller
{
    /** Clés de filtre reconnues sur la liste des paiements. */
    private const FILTER_KEYS = [
        'search', 'status', 'provider', 'service', 'categorie',
        'code', 'email', 'phone', 'reference', 'from', 'to',
    ];

    public function index(Request $request, PaymentAdminPresenter $presenter): View
    {
        $this->ensureAdmin();

        $filters = TableFilters::values($request, self::FILTER_KEYS);
        $perPage = TableFilters::perPage($request);

        $payments = $this->filteredQuery($filters)
            ->with(PaymentAdminPresenter::RELATIONS)
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $items = $payments->getCollection()
            ->map(fn (Paiements $payment) => $presenter->item($payment))
            ->values();

        return view('screen.paiements.index_paiements', [
            'payments' => $payments,
            'items' => $items,
            'filters' => $filters,
            'perPage' => $perPage,
            'filterDefinitions' => $this->filterDefinitions(),
        ]);
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        return Paiements::query()
            // Recherche transversale : le client, son compte, la transaction
            // fournisseur, les codes émis et la catégorie achetée.
            ->when($filters['search'], function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->whereLike('numero_payeur', $term)
                        ->orWhereLike('numero_client', $term)
                        ->orWhereHas('user', fn (Builder $user) => $user
                            ->whereLike('name', $term)
                            ->orWhereLike('last_name', $term)
                            ->orWhereLike('email', $term)
                            ->orWhereLike('phone', $term))
                        ->orWhereHas('transaction', fn (Builder $transaction) => $transaction
                            ->whereLike('reference', $term)
                            ->orWhereLike('provider_reference', $term)
                            ->orWhereLike('payment_token', $term)
                            ->orWhereLike('phone_number', $term))
                        ->orWhereHas('codes', fn (Builder $code) => $code->whereLike('code', $term))
                        ->orWhereHas('categorie', fn (Builder $categorie) => $categorie->whereLike('libelle', $term));

                    // Recherche par identifiant de paiement ou de transaction.
                    if (ctype_digit($term)) {
                        $query->orWhere('id', (int) $term)
                            ->orWhere('transaction_id', (int) $term);
                    }
                });
            })
            ->when($filters['status'], fn (Builder $query, string $status) => $this->applyStatusFilter($query, $status))
            ->when($filters['provider'], fn (Builder $query, string $provider) => $query
                ->whereHas('transaction', fn (Builder $transaction) => $transaction
                    ->where('payment_provider_id', $provider)))
            ->when($filters['service'], fn (Builder $query, string $service) => $query
                ->whereHas('transaction', fn (Builder $transaction) => $transaction
                    ->where('service_id', $service)))
            ->when($filters['categorie'], fn (Builder $query, string $categorie) => $query
                ->where('categorie_id', $categorie))
            ->when($filters['code'], fn (Builder $query, string $code) => $query
                ->whereHas('codes', fn (Builder $codes) => $codes->whereLike('code', $code)))
            ->when($filters['email'], fn (Builder $query, string $email) => $query
                ->whereHas('user', fn (Builder $user) => $user->whereLike('email', $email)))
            ->when($filters['phone'], fn (Builder $query, string $phone) => $query
                ->where(fn (Builder $query) => $query
                    ->whereLike('numero_payeur', $phone)
                    ->orWhereLike('numero_client', $phone)
                    ->orWhereHas('user', fn (Builder $user) => $user->whereLike('phone', $phone))
                    ->orWhereHas('transaction', fn (Builder $transaction) => $transaction
                        ->whereLike('phone_number', $phone))))
            ->when($filters['reference'], fn (Builder $query, string $reference) => $query
                ->whereHas('transaction', fn (Builder $transaction) => $transaction
                    ->whereLike('reference', $reference)
                    ->orWhereLike('provider_reference', $reference)
                    ->orWhereLike('payment_token', $reference)))
            ->when($filters['from'], fn (Builder $query, string $from) => $query
                ->whereDate('created_at', '>=', $from))
            ->when($filters['to'], fn (Builder $query, string $to) => $query
                ->whereDate('created_at', '<=', $to));
    }

    /**
     * Les statuts affichés sont calculés par le présentateur à partir de trois
     * sources (révocation, validation locale, statut fournisseur) : le filtre
     * reproduit exactement ces règles en SQL.
     */
    private function applyStatusFilter(Builder $query, string $status): Builder
    {
        $validated = fn (Builder $query) => $query
            ->whereNull('revoked_at')
            ->where('status', true)
            ->whereNotNull('paiement_date');

        $transactionStatusIs = fn (Builder $query, string $value) => $query
            ->whereNull('revoked_at')
            ->where(fn (Builder $inner) => $inner->where('status', false)->orWhereNull('paiement_date'))
            ->whereHas('transaction', fn (Builder $transaction) => $transaction
                ->where('status', $value));

        return match ($status) {
            'revoked' => $query->whereNotNull('revoked_at'),
            'validated' => $validated($query),
            'failed' => $transactionStatusIs($query, TransactionStatus::FAILED->value),
            'processing' => $transactionStatusIs($query, TransactionStatus::PROCESSING->value),
            'to_finalize' => $transactionStatusIs($query, TransactionStatus::SUCCESS->value),
            'pending' => $query
                ->whereNull('revoked_at')
                ->where(fn (Builder $inner) => $inner->where('status', false)->orWhereNull('paiement_date'))
                ->where(fn (Builder $inner) => $inner
                    ->whereDoesntHave('transaction')
                    ->orWhereHas('transaction', fn (Builder $transaction) => $transaction
                        ->where('status', TransactionStatus::PENDING->value))),
            default => $query,
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filterDefinitions(): array
    {
        return [
            [
                'key' => 'status',
                'label' => 'Statut',
                'display' => 'chips',
                'allLabel' => 'Tous',
                'options' => [
                    ['value' => 'pending', 'label' => 'En attente'],
                    ['value' => 'processing', 'label' => 'Traitement'],
                    ['value' => 'to_finalize', 'label' => 'À finaliser'],
                    ['value' => 'validated', 'label' => 'Validé'],
                    ['value' => 'failed', 'label' => 'Échoué'],
                    ['value' => 'revoked', 'label' => 'Révoqué'],
                ],
            ],
            [
                'key' => 'provider',
                'label' => 'Fournisseur',
                'type' => 'select',
                'options' => TableFilters::options(PaymentProvider::orderBy('name')->get(), 'name'),
            ],
            [
                'key' => 'service',
                'label' => 'Service de paiement',
                'type' => 'select',
                'options' => TableFilters::options(PayementServices::orderBy('title')->get(), 'title'),
            ],
            [
                'key' => 'categorie',
                'label' => 'Catégorie',
                'type' => 'select',
                'options' => TableFilters::options(Categorie::orderBy('libelle')->get()),
            ],
            ['key' => 'code', 'label' => 'Code', 'type' => 'text', 'placeholder' => 'Code contient…'],
            ['key' => 'email', 'label' => 'Email', 'type' => 'text', 'placeholder' => 'Email contient…'],
            ['key' => 'phone', 'label' => 'Téléphone', 'type' => 'text', 'placeholder' => 'Numéro contient…'],
            ['key' => 'reference', 'label' => 'Référence', 'type' => 'text', 'placeholder' => 'Référence contient…'],
            ['key' => 'from', 'label' => 'Depuis le', 'type' => 'date'],
            ['key' => 'to', 'label' => 'Jusqu’au', 'type' => 'date'],
        ];
    }

    public function active(Paiements $paiement, PaymentAdminPresenter $presenter): View
    {
        $this->ensureAdmin();
        $paiement->load(PaymentAdminPresenter::RELATIONS);

        if ($paiement->transaction && Schema::hasTable('transaction_logs')) {
            $paiement->transaction->setRelation(
                'logs',
                $paiement->transaction->logs()->limit(50)->get(),
            );
        } elseif ($paiement->transaction) {
            $paiement->transaction->setRelation('logs', collect());
        }

        return view('screen.paiements.active_paiement', [
            'paie' => $paiement,
            'status' => $presenter->status($paiement),
            'actions' => $presenter->actions($paiement),
        ]);
    }

    public function reverify(Paiements $paiement, Dispatcher $dispatcher): RedirectResponse
    {
        $this->ensureAdmin();
        $paiement->load('transaction');

        if (! $paiement->transaction) {
            return back()->with('error', 'Aucune transaction n’est associée à ce paiement.');
        }

        if (blank($paiement->transaction->provider_reference)) {
            return back()->with('error', 'La référence fournisseur est absente : la transaction ne peut pas être revérifiée.');
        }

        $result = $dispatcher->dispatchSync(
            new VerifyPendingTransaction($paiement->transaction->id, dryRun: false, force: true)
        );

        if (! $result instanceof TransactionVerificationResult || $result->isError()) {
            $message = $result instanceof TransactionVerificationResult ? $result->message : null;

            return back()->with('error', $message ?: 'La revérification du paiement a échoué.');
        }

        return back()->with(
            'success',
            "Transaction revérifiée. Statut fournisseur : {$result->providerStatus}; statut local : {$result->outcome}.",
        );
    }

    public function activate(
        Paiements $paiement,
        PaiementService $paiementService,
        TransactionFinalizationService $finalizer,
    ): RedirectResponse {
        $this->ensureAdmin();
        $paiement->load(['transaction', 'codes']);

        if ($paiement->revoked_at !== null) {
            return back()->with('error', 'Cet abonnement est révoqué et ne peut pas être réactivé.');
        }

        try {
            if ($paiement->transaction) {
                $finalizer->applyStatus(
                    transaction: $paiement->transaction,
                    status: TransactionStatus::SUCCESS,
                    context: [
                        'admin_id' => auth()->id(),
                        'manual_activation' => true,
                    ],
                    source: 'admin_manual',
                );
            } else {
                $paiementService->validePayment(['paiement' => $paiement->id]);
            }

            $paiement->refresh();

            if (! $paiement->status || ! $paiement->paiement_date) {
                return back()->with('error', 'Le paiement n’a pas pu être finalisé. Consultez les logs avant de réessayer.');
            }

            return back()->with('success', 'Paiement activé et codes contrôlés avec succès.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Activation impossible : '.$exception->getMessage());
        }
    }

    public function resendNotification(Paiements $paiement): RedirectResponse
    {
        $this->ensureAdmin();
        $paiement->load(['user', 'codes']);

        if ($paiement->revoked_at !== null) {
            return back()->with('error', 'Cet abonnement est révoqué : ses codes ne peuvent plus être renvoyés.');
        }

        if (! $paiement->status || ! $paiement->paiement_date) {
            return back()->with('error', 'Le paiement doit être validé avant de renvoyer ses codes.');
        }

        if ($paiement->codes->isEmpty()) {
            return back()->with('error', 'Aucun code n’a encore été généré pour ce paiement.');
        }

        if (! $paiement->user) {
            return back()->with('error', 'L’utilisateur associé au paiement est introuvable.');
        }

        $messageService = new SendMessageService($paiement, $paiement->user);
        $codes = $paiement->codes->whereNull('revoked_at')->pluck('code')->all();

        if ($codes === []) {
            return back()->with('error', 'Tous les codes de cet abonnement sont révoqués.');
        }

        if (count($codes) === 1) {
            if (! $messageService->sendSMS($codes[0])) {
                return back()->with('error', 'Le fournisseur SMS a refusé ou interrompu l’envoi.');
            }

            return back()->with('success', 'Le code a été renvoyé par SMS au bénéficiaire.');
        }

        SendMailJob::dispatch($messageService, $codes);

        return back()->with('success', 'Le renvoi de la liste des codes par e-mail a été programmé.');
    }

    public function revoke(
        RevokeAccessRequest $request,
        Paiements $paiement,
        AccessRevocationService $revocationService,
    ): RedirectResponse {
        $revokedCodes = $revocationService->revokeSubscription(
            $paiement,
            (int) $request->user()->id,
            $request->validated('reason'),
        );

        return back()->with(
            'success',
            "L’abonnement et {$revokedCodes} code(s) associé(s) ont été révoqués.",
        );
    }

    /**
     * Route historique conservée pour ne pas casser les anciens formulaires.
     */
    public function valide(Request $request, PaiementService $paiementService): RedirectResponse
    {
        $this->ensureAdmin();
        $validated = $request->validate(['paiement' => ['required', 'integer', 'exists:paiements,id']]);
        $paiement = Paiements::findOrFail($validated['paiement']);

        if ($paiement->revoked_at !== null) {
            return back()->with('error', 'Cet abonnement est révoqué et ne peut pas être réactivé.');
        }

        $paiementService->validePayment($validated);

        return to_route('paiement.active', $validated['paiement'])
            ->with('success', 'Paiement activé et codes contrôlés avec succès.');
    }

    private function ensureAdmin(): void
    {
        abort_unless((int) auth()->user()?->rule_id === 1, 403);
    }
}
