@extends('nav')

@section('title', 'Code '.$code->code)

@section('content')
@php
    $paiement = $code->paiement;
    $user = $paiement->user;
    $studentName = trim(($user?->name ?? '').' '.($user?->last_name ?? '')) ?: 'Élève inconnu';
    [$status, $tone] = match (true) {
        $code->revoked_at !== null || $paiement->revoked_at !== null => ['Révoqué', 'danger'],
        (bool) $code->actif => ['Activé', 'success'],
        default => ['En attente d’activation', 'warning'],
    };
    $deliverable = $tone === 'warning';
    $canSms = $deliverable && \App\Support\PhoneNumber::isValidCameroonMobile($paiement->numero_client);
    $canEmail = $deliverable && filter_var($user?->email, FILTER_VALIDATE_EMAIL);
    $granter = $paiement->granter ? trim($paiement->granter->name.' '.$paiement->granter->last_name) : null;
@endphp

<section class="admin-page payment-detail-page">
    <a class="admin-back-link" href="{{ route('codes.index', 'all') }}">← Retour aux codes</a>

    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Code offert</p>
            <h1>Code d’activation attribué</h1>
            <p>Partagez ce code avec l’élève : il le saisit dans l’application pour débloquer la catégorie.</p>
        </div>
        <div class="payment-heading-actions">
            <span class="status-pill {{ $tone }}">{{ $status }}</span>
            <a href="{{ route('codes.grant.create') }}" class="admin-button secondary">Attribuer un autre code</a>
        </div>
    </div>

    <div class="entity-form-grid">
        <div class="entity-form-main">
            <article class="admin-panel form-panel">
                <div class="code-grant-code">
                    <div>
                        <span>Code d’activation</span>
                        <strong>{{ $code->code }}</strong>
                        <small>{{ $paiement->categorie?->libelle ?? 'Catégorie inconnue' }}</small>
                    </div>
                    <admin-copy-button value="{{ $code->code }}" label="Copier le code" variant="primary"></admin-copy-button>
                </div>

                <div class="code-grant-message">
                    <div>
                        <span>Message prêt à envoyer</span>
                        <p v-pre>{{ $shareMessage }}</p>
                    </div>
                    <admin-copy-button value="{{ $shareMessage }}" label="Copier le message"></admin-copy-button>
                </div>
            </article>

            <article class="admin-panel form-panel">
                <div class="form-section-heading"><span>✉</span><div><h2>Envoyer le code</h2><p>{{ $deliverable ? 'Chaque envoi peut être relancé si l’élève ne l’a pas reçu.' : 'Un code activé ou révoqué ne peut plus être envoyé.' }}</p></div></div>

                <div class="entity-status-card">
                    <div>
                        <strong>Par SMS</strong>
                        <small v-pre>{{ $paiement->numero_client ?: 'Aucun numéro associé au code.' }}</small>
                    </div>
                    <form method="POST" action="{{ route('codes.grant.sms', $code) }}">
                        @csrf
                        <button type="submit" class="admin-button secondary compact" @disabled(! $canSms)>Envoyer le SMS</button>
                    </form>
                </div>

                <div class="entity-status-card">
                    <div>
                        <strong>Par e-mail</strong>
                        <small v-pre>{{ $user?->email ?: 'Aucune adresse e-mail sur le compte de l’élève.' }}</small>
                    </div>
                    <form method="POST" action="{{ route('codes.grant.email', $code) }}">
                        @csrf
                        <button type="submit" class="admin-button secondary compact" @disabled(! $canEmail)>Envoyer l’e-mail</button>
                    </form>
                </div>
            </article>
        </div>

        <aside class="entity-form-side">
            <article class="admin-panel" v-pre>
                <div class="admin-panel-head"><div><p class="admin-eyebrow">Attribution</p><h2>Détails</h2></div></div>
                <dl class="payment-detail-list compact">
                    <div><dt>Élève</dt><dd>{{ $studentName }}</dd></div>
                    <div><dt>Catégorie</dt><dd>{{ $paiement->categorie?->libelle ?? '—' }}</dd></div>
                    <div><dt>Attribué par</dt><dd>{{ $granter ?? '—' }}</dd></div>
                    <div><dt>Attribué le</dt><dd>{{ $code->created_at?->format('d/m/Y à H:i') }}</dd></div>
                    <div><dt>Motif</dt><dd>{{ $paiement->grant_reason ?? '—' }}</dd></div>
                    <div><dt>Activé le</dt><dd>{{ $code->active_date ? \Carbon\Carbon::parse($code->active_date)->format('d/m/Y à H:i') : 'Pas encore activé' }}</dd></div>
                    @if ($code->revoked_at)
                        <div><dt>Révoqué le</dt><dd>{{ $code->revoked_at->format('d/m/Y à H:i') }}<br><small>{{ $code->revocation_reason }}</small></dd></div>
                    @endif
                </dl>
            </article>
        </aside>
    </div>
</section>
@endsection
