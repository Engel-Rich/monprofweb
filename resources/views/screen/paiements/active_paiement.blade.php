@extends('nav')

@section('title', 'Paiement #'.$paie->id)

@section('content')
    @php
        $transaction = $paie->transaction;
        $service = $transaction?->paymentService;
        $provider = $transaction?->provider ?? $service?->provider;
        $checks = collect(data_get($transaction?->metadatas, 'status_checks', []))->reverse()->values();
        $statusTone = match ($status) {
            'Validé' => 'success',
            'Échoué' => 'danger',
            default => 'warning',
        };
        $clientName = trim(($paie->user?->name ?? '').' '.($paie->user?->last_name ?? '')) ?: 'Utilisateur inconnu';
    @endphp

    <section class="admin-page payment-detail-page">
        <a class="admin-back-link" href="{{ route('paiement.index') }}">← Retour aux paiements</a>

        <div class="admin-page-heading payment-detail-heading">
            <div>
                <p class="admin-eyebrow">Paiement #{{ $paie->id }}</p>
                <h1>Détails du paiement</h1>
                <p>Informations financières, transaction fournisseur, codes et historique de vérification.</p>
            </div>
            <div class="payment-heading-actions">
                <span class="status-pill {{ $statusTone }}">{{ $status }}</span>
                <admin-action-buttons :actions="{{ Js::from($actions) }}"></admin-action-buttons>
            </div>
        </div>

        <div class="payment-metric-grid">
            <article>
                <span>Montant MonProf</span>
                <strong>{{ number_format((float) $paie->montant, 0, ',', ' ') }} FCFA</strong>
                <small>{{ $paie->categorie?->libelle ?? 'Catégorie inconnue' }}</small>
            </article>
            <article>
                <span>Transaction fournisseur</span>
                <strong>{{ $transaction?->status ?? 'Absente' }}</strong>
                <small>{{ $provider?->name ?? 'Aucun fournisseur associé' }}</small>
            </article>
            <article>
                <span>Codes</span>
                <strong>{{ $paie->codes->count() }} / {{ $paie->nombre_de_code }}</strong>
                <small>{{ $paie->codes->where('actif', true)->count() }} utilisé(s)</small>
            </article>
            <article>
                <span>Dernière vérification</span>
                <strong>{{ data_get($transaction?->metadatas, 'last_status_check_at') ? \Carbon\Carbon::parse(data_get($transaction?->metadatas, 'last_status_check_at'))->format('d/m H:i') : 'Jamais' }}</strong>
                <small>Créé le {{ $paie->created_at?->format('d/m/Y à H:i') }}</small>
            </article>
        </div>

        <div class="payment-detail-grid">
            <div class="payment-detail-main">
                <article class="admin-panel payment-detail-card">
                    <header class="payment-card-heading">
                        <div><p class="admin-eyebrow">Transaction</p><h2>Suivi fournisseur</h2></div>
                        @if ($provider || $service)
                            <div class="payment-provider-chip">
                                @if ($provider?->image_url || $service?->image_url)
                                    <img src="{{ $provider?->image_url ?? $service?->image_url }}" alt="">
                                @endif
                                <span>
                                    <strong>{{ $provider?->name ?? $service?->title }}</strong>
                                    <small>{{ $provider?->code ?? ('Service #'.$service?->id) }}</small>
                                </span>
                            </div>
                        @endif
                    </header>

                    @if ($transaction)
                        <div class="payment-reference-banner {{ strtolower($transaction->status) }}">
                            <div>
                                <span>Référence fournisseur</span>
                                <strong>{{ $transaction->provider_reference ?: 'Non disponible' }}</strong>
                                <small>{{ $transaction->reference ?: 'Aucune référence MonProf' }}</small>
                            </div>
                            <span class="status-pill {{ strtolower($transaction->status) === 'success' ? 'success' : (strtolower($transaction->status) === 'failed' ? 'danger' : 'warning') }}">{{ $transaction->status }}</span>
                        </div>

                        <dl class="payment-detail-list">
                            <div><dt>ID transaction locale</dt><dd><code>#{{ $transaction->id }}</code></dd></div>
                            <div><dt>Pay token</dt><dd><code>{{ $transaction->payment_token ?: '—' }}</code></dd></div>
                            <div><dt>Montant fournisseur</dt><dd>{{ number_format((float) $transaction->amount, 0, ',', ' ') }} FCFA</dd></div>
                            <div><dt>Téléphone</dt><dd><a href="tel:{{ $transaction->phone_number }}">{{ $transaction->phone_number ?: '—' }}</a></dd></div>
                            <div><dt>Sens</dt><dd>{{ $transaction->sens ?: '—' }}</dd></div>
                            <div><dt>Service interne</dt><dd><code>{{ $transaction->internal_service ?: '—' }}</code></dd></div>
                            <div><dt>Subscription ID</dt><dd><code>{{ $transaction->subscription_id ?: '—' }}</code></dd></div>
                            <div><dt>Service de paiement</dt><dd>{{ $service?->title ?: '—' }} @if($service)<small>#{{ $service->id }}</small>@endif</dd></div>
                            <div><dt>Créée le</dt><dd>{{ $transaction->created_at?->format('d/m/Y à H:i:s') }}</dd></div>
                            <div><dt>Mise à jour le</dt><dd>{{ $transaction->updated_at?->format('d/m/Y à H:i:s') }}</dd></div>
                        </dl>

                        @if ($transaction->raison_reject)
                            <div class="payment-rejection"><strong>Motif de l’échec</strong><span>{{ $transaction->raison_reject }}</span></div>
                        @endif
                    @else
                        <div class="admin-empty-state large"><strong>Aucune transaction associée</strong><span>Ce paiement ne peut pas être revérifié automatiquement.</span></div>
                    @endif
                </article>

                <article class="admin-panel payment-detail-card">
                    <header class="payment-card-heading">
                        <div><p class="admin-eyebrow">Historique</p><h2>Vérifications du statut</h2></div>
                        <span class="payment-section-count">{{ $checks->count() }} contrôle(s)</span>
                    </header>

                    @if ($checks->isNotEmpty())
                        <div class="payment-timeline">
                            @foreach ($checks as $check)
                                @php
                                    $checkStatus = strtoupper((string) ($check['status'] ?? 'PENDING'));
                                    $checkTone = $checkStatus === 'SUCCESS' ? 'success' : ($checkStatus === 'FAILED' ? 'danger' : 'warning');
                                @endphp
                                <div class="payment-timeline-item {{ $checkTone }}">
                                    <span class="payment-timeline-dot"></span>
                                    <div>
                                        <div><strong>{{ $checkStatus }}</strong><small>{{ $check['source'] ?? 'polling' }}</small></div>
                                        <p>{{ !empty($check['checked_at']) ? \Carbon\Carbon::parse($check['checked_at'])->format('d/m/Y à H:i:s') : 'Date inconnue' }}</p>
                                        @if (!empty($check['provider']))<code>{{ $check['provider'] }}</code>@endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="admin-empty-state"><strong>Aucune vérification enregistrée</strong><span>Utilisez « Revérifier » pour interroger le fournisseur.</span></div>
                    @endif

                    @if ($transaction?->metadatas)
                        <details class="payment-technical-details">
                            <summary>Afficher toutes les métadonnées techniques</summary>
                            <pre>{{ json_encode($transaction->metadatas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                    @endif
                </article>
            </div>

            <aside class="payment-detail-side">
                <article class="admin-panel payment-detail-card">
                    <header class="payment-card-heading"><div><p class="admin-eyebrow">Paiement</p><h2>Commande MonProf</h2></div></header>
                    <dl class="payment-detail-list compact">
                        <div><dt>ID paiement</dt><dd><code>#{{ $paie->id }}</code></dd></div>
                        <div><dt>Statut</dt><dd><span class="status-pill {{ $statusTone }}">{{ $status }}</span></dd></div>
                        <div><dt>Montant</dt><dd><strong>{{ number_format((float) $paie->montant, 0, ',', ' ') }} FCFA</strong></dd></div>
                        <div><dt>Catégorie</dt><dd>{{ $paie->categorie?->libelle ?: '—' }}</dd></div>
                        <div><dt>Quantité</dt><dd>{{ $paie->nombre_de_code }} code(s)</dd></div>
                        <div><dt>Validation</dt><dd>{{ $paie->paiement_date?->format('d/m/Y à H:i:s') ?: 'Non validé' }}</dd></div>
                    </dl>
                </article>

                <article class="admin-panel payment-detail-card">
                    <header class="payment-card-heading"><div><p class="admin-eyebrow">Client</p><h2>{{ $clientName }}</h2></div></header>
                    <dl class="payment-detail-list compact">
                        <div><dt>ID utilisateur</dt><dd><code>#{{ $paie->user_id }}</code></dd></div>
                        <div><dt>E-mail</dt><dd><a href="mailto:{{ $paie->user?->email }}">{{ $paie->user?->email ?: '—' }}</a></dd></div>
                        <div><dt>Numéro débité</dt><dd><a href="tel:{{ $paie->numero_payeur }}">{{ $paie->numero_payeur ?: '—' }}</a></dd></div>
                        <div><dt>Numéro à notifier</dt><dd><a href="tel:{{ $paie->numero_client }}">{{ $paie->numero_client ?: '—' }}</a></dd></div>
                    </dl>
                </article>

                <article class="admin-panel payment-detail-card">
                    <header class="payment-card-heading">
                        <div><p class="admin-eyebrow">Accès</p><h2>Codes générés</h2></div>
                        <span class="payment-section-count">{{ $paie->codes->count() }}</span>
                    </header>
                    <div class="payment-code-list">
                        @forelse ($paie->codes as $code)
                            <div class="payment-code-item">
                                <div><code>{{ $code->code }}</code><small>{{ $code->active_date?->format('d/m/Y à H:i') ?: 'Jamais utilisé' }}</small></div>
                                <span class="status-pill {{ $code->actif ? 'success' : 'muted' }}">{{ $code->actif ? 'Activé' : 'Disponible' }}</span>
                                @if ($code->eleve?->user)
                                    <p>Utilisé par {{ trim($code->eleve->user->name.' '.$code->eleve->user->last_name) }} · Élève #{{ $code->eleve_id }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="admin-empty-state">Aucun code généré.</div>
                        @endforelse
                    </div>
                </article>
            </aside>
        </div>
    </section>
@endsection
