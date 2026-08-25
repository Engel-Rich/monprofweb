<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\RevokeAccessRequest;
use App\Models\Codes;
use App\Services\AccessRevocationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CodesController extends Controller
{
    //** */

    public function index($status): View
    {
        $this->ensureAdmin();

        $query = Codes::with('eleve.user', 'paiement', 'revoker')->latest('id');

        if ($status === 'actif') {
            $query->where('actif', true)->whereNull('revoked_at');
        } elseif ($status === 'revoque') {
            $query->whereNotNull('revoked_at');
        } elseif ($status !== 'all') {
            $query->where('actif', false)->whereNull('revoked_at');
        }

        return view('screen.codes.index_codes', ['codes' => $query->paginate(20)]);
    }

    public function revoke(
        RevokeAccessRequest $request,
        Codes $code,
        AccessRevocationService $revocationService,
    ): RedirectResponse {
        $revoked = $revocationService->revokeCode(
            $code,
            (int) $request->user()->id,
            $request->validated('reason'),
        );

        return back()->with(
            $revoked ? 'success' : 'error',
            $revoked ? 'Le code a été révoqué et ne donne plus accès aux cours.' : 'Ce code est déjà révoqué.',
        );
    }

    public function valideCode(Request $request) {}

    private function ensureAdmin(): void
    {
        abort_unless((int) auth()->user()?->rule_id === 1, 403);
    }
    /**
     * Activations du codes;
     */
}
