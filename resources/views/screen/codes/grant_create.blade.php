@extends('nav')

@section('title', 'Attribuer un code')

@section('content')
@php
    $studentName = fn ($eleve) => trim(($eleve->user?->name ?? '').' '.($eleve->user?->last_name ?? '')) ?: 'Élève sans nom';
@endphp
<section class="admin-page entity-form-page">
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Accès</p>
            <h1>Attribuer un code d’activation</h1>
            <p>Offrez à un élève l’accès à une catégorie, sans paiement. Le code peut être envoyé par SMS, par e-mail ou copié.</p>
        </div>
        <a href="{{ route('codes.index', 'all') }}" class="admin-button secondary">Retour aux codes</a>
    </div>

    <div class="entity-form-grid">
        <div class="entity-form-main">
            <article class="admin-panel form-panel">
                <div class="form-section-heading"><span>01</span><div><h2>Élève bénéficiaire</h2><p>Recherchez l’élève par son nom, son e-mail ou son numéro de téléphone.</p></div></div>

                @if ($student)
                    <div class="code-grant-student" v-pre>
                        <div>
                            <strong>{{ $studentName($student) }}</strong>
                            <small>{{ $student->classe?->libelle ?? 'Classe non renseignée' }}{{ $student->etablissement ? ' · '.$student->etablissement : '' }}</small>
                            <small>{{ $student->user?->phone ?: 'Aucun téléphone' }} · {{ $student->user?->email ?: 'Aucun e-mail' }}</small>
                        </div>
                        <a href="{{ route('codes.grant.create') }}" class="admin-button secondary compact">Changer d’élève</a>
                    </div>
                @else
                    <form method="GET" action="{{ route('codes.grant.create') }}" class="code-grant-search">
                        <div class="entity-field">
                            <label for="recherche">Rechercher un élève <em>Obligatoire</em></label>
                            <input id="recherche" name="recherche" type="search" value="{{ $search }}" placeholder="Ex. Jean Dupont, jean@exemple.cm ou 690000000" minlength="2" required autofocus>
                        </div>
                        <button type="submit" class="admin-button primary">Rechercher</button>
                    </form>

                    @if ($search !== '')
                        @if ($results->isEmpty())
                            <p class="code-grant-empty">Aucun élève ne correspond à « <span v-pre>{{ $search }}</span> ».</p>
                        @else
                            <ul class="code-grant-results" v-pre>
                                @foreach ($results as $result)
                                    <li>
                                        <div>
                                            <strong>{{ $studentName($result) }}</strong>
                                            <small>{{ $result->classe?->libelle ?? 'Classe non renseignée' }} · {{ $result->user?->phone ?: 'Aucun téléphone' }} · {{ $result->user?->email ?: 'Aucun e-mail' }}</small>
                                        </div>
                                        <a href="{{ route('codes.grant.create', ['eleve' => $result->id]) }}" class="admin-button secondary compact">Choisir</a>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($results->count() >= 8)
                                <p class="code-grant-empty">Seuls les 8 premiers résultats sont affichés : affinez la recherche si l’élève n’apparaît pas.</p>
                            @endif
                        @endif
                    @endif
                @endif
            </article>

            @if ($student)
                <form method="POST" action="{{ route('codes.grant.store') }}" class="entity-form-main">
                    @csrf
                    <input type="hidden" name="eleve_id" value="{{ $student->id }}">

                    <article class="admin-panel form-panel">
                        <div class="form-section-heading"><span>02</span><div><h2>Accès offert</h2><p>Le code débloque la catégorie choisie dès que l’élève le saisit dans l’application.</p></div></div>
                        @if ($errors->any())<div class="admin-alert error"><strong>Attribution impossible.</strong><span>{{ $errors->first() }}</span></div>@endif
                        <div class="entity-field-grid two">
                            <div class="entity-field full">
                                <label for="categorie_id">Catégorie <em>Obligatoire</em></label>
                                <select id="categorie_id" name="categorie_id" required>
                                    <option value="">Sélectionner une catégorie</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) old('categorie_id') === (string) $category->id)>{{ $category->libelle }} — {{ number_format((float) $category->prix, 0, ',', ' ') }} FCFA</option>
                                    @endforeach
                                </select>
                                @error('categorie_id')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="entity-field">
                                <label for="phone">Numéro SMS <span>Facultatif</span></label>
                                <input id="phone" name="phone" type="tel" value="{{ old('phone', $student->user?->phone) }}" placeholder="Ex. 690000000">
                                <small>Par défaut, le numéro du compte de l’élève.</small>
                                @error('phone')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="entity-field">
                                <label for="reason">Motif <span>Facultatif</span></label>
                                <input id="reason" name="reason" type="text" value="{{ old('reason') }}" maxlength="500" placeholder="Ex. Geste commercial, partenariat…">
                                <small>Visible uniquement dans la console.</small>
                                @error('reason')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </article>

                    <article class="admin-panel form-panel">
                        <div class="form-section-heading"><span>03</span><div><h2>Envoi du code</h2><p>Facultatif : le code reste consultable, copiable et renvoyable depuis sa fiche.</p></div></div>
                        <div class="entity-status-card">
                            <div><strong>Envoyer par SMS</strong><small>Au numéro indiqué ci-dessus.</small></div>
                            <label class="entity-switch"><input type="hidden" name="send_sms" value="0"><input type="checkbox" name="send_sms" value="1" @checked(old('send_sms'))><span></span><em>SMS</em></label>
                        </div>
                        <div class="entity-status-card">
                            <div><strong>Envoyer par e-mail</strong><small v-pre>{{ $student->user?->email ?: 'Aucune adresse e-mail sur ce compte.' }}</small></div>
                            <label class="entity-switch"><input type="hidden" name="send_email" value="0"><input type="checkbox" name="send_email" value="1" @checked(old('send_email')) @disabled(! $student->user?->email)><span></span><em>E-mail</em></label>
                        </div>
                    </article>

                    <div class="form-actions-card entity-form-actions"><a href="{{ route('codes.index', 'all') }}" class="admin-button secondary">Annuler</a><button type="submit" class="admin-button primary">Attribuer le code</button></div>
                </form>
            @endif
        </div>

        <aside class="entity-form-side">
            <article class="admin-panel form-help-panel">
                <span class="form-help-icon">CODE</span>
                <h2>Comment ça marche</h2>
                <p>Le code fonctionne exactement comme un code acheté : l’élève le saisit dans l’application pour débloquer la catégorie.</p>
                <ul>
                    <li>Aucun paiement n’est créé côté fournisseur.</li>
                    <li>Ces codes n’entrent pas dans les revenus ni les statistiques de paiement.</li>
                    <li>Un élève qui a déjà un abonnement actif pour la catégorie ne peut pas en recevoir un second.</li>
                    <li>Le code peut être révoqué à tout moment depuis la liste des codes.</li>
                </ul>
            </article>
        </aside>
    </div>
</section>
@endsection
