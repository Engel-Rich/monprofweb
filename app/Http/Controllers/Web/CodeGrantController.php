<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\GrantActivationCodeRequest;
use App\Models\Categorie;
use App\Models\Codes;
use App\Models\Eleve;
use App\Services\Admin\ActivationCodeGrantService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Attribution d'un code d'activation à un élève, sans paiement.
 */
class CodeGrantController extends Controller
{
    private const SEARCH_LIMIT = 8;

    public function create(Request $request): View
    {
        $this->ensureAdmin();

        $search = trim((string) $request->query('recherche', ''));
        $student = $request->filled('eleve')
            ? Eleve::with('user', 'classe')->find((int) $request->query('eleve'))
            : null;

        return view('screen.codes.grant_create', [
            'student' => $student,
            'search' => $search,
            'results' => $student || $search === '' ? collect() : $this->searchStudents($search),
            'categories' => Categorie::query()->where('status', true)->orderBy('libelle')->get(['id', 'libelle', 'prix']),
        ]);
    }

    public function store(GrantActivationCodeRequest $request, ActivationCodeGrantService $grants): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $code = $grants->grant(
                student: Eleve::with('user')->findOrFail($validated['eleve_id']),
                category: Categorie::findOrFail($validated['categorie_id']),
                admin: $request->user(),
                phone: $validated['phone'] ?? null,
                reason: $validated['reason'] ?? null,
            );
        } catch (DomainException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        // Le code existe quoi qu'il arrive : un envoi en échec est signalé
        // sans annuler l'attribution, il pourra être relancé depuis la fiche.
        $sent = [];
        $failures = [];

        foreach (['send_sms' => 'SMS', 'send_email' => 'e-mail'] as $field => $channel) {
            if (! $request->boolean($field)) {
                continue;
            }

            try {
                $field === 'send_sms' ? $grants->sendSms($code) : $grants->sendEmail($code);
                $sent[] = $channel;
            } catch (DomainException $exception) {
                $failures[] = "{$channel} : {$exception->getMessage()}";
            }
        }

        $redirect = redirect()
            ->route('codes.grant.show', $code)
            ->with('success', 'Code '.$code->code.' attribué.'.($sent ? ' Envoyé par '.implode(' et ', $sent).'.' : ''));

        return $failures ? $redirect->with('error', 'Envoi impossible — '.implode(' ; ', $failures)) : $redirect;
    }

    public function show(Codes $code, ActivationCodeGrantService $grants): View
    {
        $this->ensureAdmin();
        $code->load('paiement.categorie', 'paiement.user', 'paiement.granter', 'eleve.user', 'revoker');
        abort_unless($code->paiement?->isAdminGrant(), 404);

        return view('screen.codes.grant_show', [
            'code' => $code,
            'shareMessage' => $grants->smsMessage($code),
        ]);
    }

    public function sendSms(Codes $code, ActivationCodeGrantService $grants): RedirectResponse
    {
        return $this->deliver($code, fn () => $grants->sendSms($code), 'Code envoyé par SMS au '.$code->paiement?->numero_client.'.');
    }

    public function sendEmail(Codes $code, ActivationCodeGrantService $grants): RedirectResponse
    {
        return $this->deliver($code, fn () => $grants->sendEmail($code), 'Code envoyé par e-mail à '.$code->paiement?->user?->email.'.');
    }

    private function deliver(Codes $code, callable $send, string $success): RedirectResponse
    {
        $this->ensureAdmin();

        try {
            $send();
        } catch (DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $success);
    }

    private function searchStudents(string $search)
    {
        $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return Eleve::query()
            ->with('user', 'classe')
            ->whereHas('user', function (Builder $user) use ($search, $words): void {
                $user->where(function (Builder $query) use ($search, $words): void {
                    // Chaque mot doit correspondre au nom ou au prénom.
                    $query->where(function (Builder $names) use ($words): void {
                        foreach ($words as $word) {
                            $names->where(fn (Builder $inner) => $inner
                                ->whereLike('name', $word)
                                ->orWhereLike('last_name', $word));
                        }
                    })
                        ->orWhereLike('email', $search)
                        ->orWhereLike('phone', $search);
                });
            })
            ->latest('id')
            ->limit(self::SEARCH_LIMIT)
            ->get();
    }

    private function ensureAdmin(): void
    {
        abort_unless((int) auth()->user()?->rule_id === 1, 403);
    }
}
