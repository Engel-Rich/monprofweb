@extends('nav')

@php($isEdit = filled($provider->id))
@section('title', $isEdit ? 'Modifier le fournisseur' : 'Nouveau fournisseur')

@section('content')
<section class="admin-page entity-form-page">
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Configuration des paiements</p>
            <h1>{{ $isEdit ? 'Modifier '.$provider->name : 'Ajouter un fournisseur' }}</h1>
            <p>Configurez l’identité de la passerelle et choisissez celle qui traite les nouvelles transactions.</p>
        </div>
        <a href="{{ route('admin.payment-providers.index') }}" class="admin-button secondary">Retour aux fournisseurs</a>
    </div>

    <div class="entity-form-grid">
        <div class="entity-form-main">
            <form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('admin.payment-providers.update', $provider) : route('admin.payment-providers.store') }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <article class="admin-panel form-panel">
                    <div class="form-section-heading"><span>01</span><div><h2>Identité du fournisseur</h2><p>Le code relie cet enregistrement à son intégration technique.</p></div></div>
                    @if ($errors->any())<div class="admin-alert error"><strong>Enregistrement impossible.</strong><span>{{ $errors->first() }}</span></div>@endif
                    <div class="entity-field-grid two">
                        <div class="entity-field"><label for="name">Nom <em>Obligatoire</em></label><input id="name" name="name" type="text" value="{{ old('name', $provider->name) }}" placeholder="Ex. CamPay" required autofocus>@error('name')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field"><label for="code">Code technique <em>Obligatoire</em></label><input id="code" name="code" type="text" value="{{ old('code', $provider->code) }}" placeholder="Ex. CAMPAY" pattern="[A-Za-z0-9_]+" required><small>Majuscules, chiffres et tirets bas uniquement.</small>@error('code')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field full"><label for="image_file">Logo <span>Facultatif</span></label><input id="image_file" name="image_file" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml"><small>PNG, JPG, WEBP ou SVG — 5 Mo maximum.</small>@error('image_file')<span class="field-error">{{ $message }}</span>@enderror</div>
                    </div>
                    @if($provider->image_url)<div class="entity-current-image"><img src="{{ $provider->image_url }}" alt="Logo de {{ $provider->name }}"><div><strong>Logo actuel</strong><span>Une nouvelle image le remplacera.</span></div></div>@endif
                    <div class="entity-status-card">
                        <div><strong>Fournisseur actif</strong><small>Une seule passerelle peut être active. Son activation désactive automatiquement la précédente.</small></div>
                        <label class="entity-switch"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $provider->is_active) ? 'checked' : '' }}><span></span><em>Actif</em></label>
                    </div>
                </article>
                <div class="form-actions-card entity-form-actions"><a href="{{ route('admin.payment-providers.index') }}" class="admin-button secondary">Annuler</a><button type="submit" class="admin-button primary">{{ $isEdit ? 'Enregistrer les modifications' : 'Créer le fournisseur' }}</button></div>
            </form>
        </div>
        <aside class="entity-form-side">
            <article class="admin-panel form-help-panel"><span class="form-help-icon">PSP</span><h2>Activation maîtrisée</h2><p>Le fournisseur actif est désormais lu depuis la base pour chaque nouvelle transaction.</p><ul><li>Le code doit exister dans la configuration technique.</li><li>Un fournisseur inactif reste administrable.</li><li>Le logo est entièrement facultatif.</li></ul></article>
            @if($isEdit && !$provider->is_active)<article class="admin-panel danger-zone"><p class="admin-eyebrow">Zone sensible</p><h2>Supprimer le fournisseur</h2><p>Les services existants conserveront toutes leurs données, mais ne seront plus rattachés à ce fournisseur.</p><form action="{{ route('admin.payment-providers.destroy', $provider) }}" method="POST">@csrf @method('DELETE')<button type="submit" class="admin-button danger" onclick="return confirm('Voulez-vous vraiment supprimer ce fournisseur ?')">Supprimer le fournisseur</button></form></article>@endif
        </aside>
    </div>
</section>
@endsection
