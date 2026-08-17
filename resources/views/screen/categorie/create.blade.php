@extends('nav')

@php($isEdit = filled($categorie->id))
@section('title', $isEdit ? 'Modifier la catégorie' : 'Nouvelle catégorie')

@section('content')
<section class="admin-page entity-form-page">
    <div class="admin-page-heading">
        <div><p class="admin-eyebrow">Offres et tarification</p><h1>{{ $isEdit ? 'Modifier '.$categorie->libelle : 'Ajouter une catégorie' }}</h1><p>Configurez l’offre, son tarif et sa disponibilité dans MonProf.</p></div>
        <a href="{{ route('categorie.index') }}" class="admin-button secondary">Retour aux catégories</a>
    </div>

    <div class="entity-form-grid">
        <div class="entity-form-main">
            <form method="POST" action="{{ $isEdit ? route('categorie.update', $categorie) : route('categorie.store') }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <article class="admin-panel form-panel">
                    <div class="form-section-heading"><span>01</span><div><h2>Informations de l’offre</h2><p>Le nom, le prix et la description affichés dans le catalogue.</p></div></div>
                    @if ($errors->any())<div class="admin-alert error"><strong>Enregistrement impossible.</strong><span>{{ $errors->first() }}</span></div>@endif
                    <div class="entity-field-grid two">
                        <div class="entity-field"><label for="libelle">Nom de la catégorie <em>Obligatoire</em></label><input id="libelle" name="libelle" type="text" value="{{ old('libelle', $categorie->libelle) }}" placeholder="Ex. Pack Excellence" required autofocus>@error('libelle')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field"><label for="prix">Prix en FCFA <em>Obligatoire</em></label><div class="entity-input-suffix"><input id="prix" name="prix" type="number" min="0" step="1" value="{{ old('prix', $categorie->prix) }}" placeholder="5000" required><span>FCFA</span></div>@error('prix')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field full"><label for="description">Description <span>Facultatif</span></label><textarea id="description" name="description" rows="5" placeholder="Expliquez ce que cette catégorie donne comme accès…">{{ old('description', $categorie->description) }}</textarea>@error('description')<span class="field-error">{{ $message }}</span>@enderror</div>
                    </div>
                    <div class="entity-status-card">
                        <div><strong>Disponibilité de la catégorie</strong><small>Une catégorie inactive reste enregistrée mais n’est pas proposée comme offre active.</small></div>
                        <label class="entity-switch"><input type="hidden" name="status" value="0"><input type="checkbox" name="status" value="1" {{ old('status', $categorie->status) ? 'checked' : '' }}><span></span><em>Active</em></label>
                    </div>
                </article>
                <div class="form-actions-card entity-form-actions"><a href="{{ route('categorie.index') }}" class="admin-button secondary">Annuler</a><button type="submit" class="admin-button primary">{{ $isEdit ? 'Enregistrer les modifications' : 'Créer la catégorie' }}</button></div>
            </form>
        </div>
        <aside class="entity-form-side">
            <article class="admin-panel form-help-panel"><span class="form-help-icon">OF</span><h2>Une offre compréhensible</h2><p>Une catégorie claire permet de mieux comprendre les revenus dans les statistiques.</p><ul><li>Utilisez un nom orienté bénéfice.</li><li>Vérifiez le prix avant activation.</li><li>Décrivez précisément l’accès proposé.</li></ul></article>
            @if($isEdit)<article class="admin-panel danger-zone"><p class="admin-eyebrow">Zone sensible</p><h2>Supprimer la catégorie</h2><p>La suppression peut affecter les cours et paiements qui y sont rattachés.</p><form action="{{ route('categorie.destroy', $categorie) }}" method="POST">@csrf @method('DELETE')<button type="submit" class="admin-button danger" onclick="return confirm('Voulez-vous vraiment supprimer cette catégorie ?')">Supprimer définitivement</button></form></article>@endif
        </aside>
    </div>
</section>
@endsection
