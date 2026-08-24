<?php

namespace App\Http\Controllers\Web;

use App\DTO\TransactionVerificationResult;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Jobs\SendMailJob;
use App\Jobs\VerifyPendingTransaction;
use App\Models\Paiements;
use App\Services\Admin\PaymentAdminPresenter;
use App\Services\PaiementService;
use App\Services\Payments\TransactionFinalizationService;
use App\Services\SendMessageService;
use Illuminate\Bus\Dispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class PaiementsController extends Controller
{
    public function index(PaymentAdminPresenter $presenter): View
    {
        $this->ensureAdmin();

        $payments = Paiements::query()
            ->with(PaymentAdminPresenter::RELATIONS)
            ->latest('id')
            ->paginate(25);
        $items = $payments->getCollection()
            ->map(fn (Paiements $payment) => $presenter->item($payment))
            ->values();

        return view('screen.paiements.index_paiements', compact('payments', 'items'));
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

        $result = $dispatcher->dispatchNow(
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
        $codes = $paiement->codes->pluck('code')->all();

        if (count($codes) === 1) {
            if (! $messageService->sendSMS($codes[0])) {
                return back()->with('error', 'Le fournisseur SMS a refusé ou interrompu l’envoi.');
            }

            return back()->with('success', 'Le code a été renvoyé par SMS au bénéficiaire.');
        }

        SendMailJob::dispatch($messageService, $codes);

        return back()->with('success', 'Le renvoi de la liste des codes par e-mail a été programmé.');
    }

    /**
     * Route historique conservée pour ne pas casser les anciens formulaires.
     */
    public function valide(Request $request, PaiementService $paiementService): RedirectResponse
    {
        $this->ensureAdmin();
        $validated = $request->validate(['paiement' => ['required', 'integer', 'exists:paiements,id']]);
        $paiementService->validePayment($validated);

        return to_route('paiement.active', $validated['paiement'])
            ->with('success', 'Paiement activé et codes contrôlés avec succès.');
    }

    private function ensureAdmin(): void
    {
        abort_unless((int) auth()->user()?->rule_id === 1, 403);
    }
}
