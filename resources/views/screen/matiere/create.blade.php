@extends('nav')

@php($isEdit = isset($matiere) && $matiere)
@section('title', $isEdit ? 'Modifier la matière' : 'Nouvelle matière')

@section('content')
<section class="admin-page entity-form-page">
    <div class="admin-page-heading">
        <div><p class="admin-eyebrow">Catalogue pédagogique</p><h1>{{ $isEdit ? 'Modifier '.$matiere->libelle : 'Ajouter une matière' }}</h1><p>Définissez un intitulé clair et son identifiant utilisé dans l’application.</p></div>
        <a href="{{ route('matiere.index') }}" class="admin-button secondary">Retour aux matières</a>
    </div>

    <div class="entity-form-grid">
        <div class="entity-form-main">
            <form method="POST" action="{{ $isEdit ? route('matiere.update', $matiere) : route('matiere.store') }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <article class="admin-panel form-panel">
                    <div class="form-section-heading"><span>01</span><div><h2>Informations de la matière</h2><p>Ces données sont utilisées dans les cours et les filtres du catalogue.</p></div></div>
                    @if ($errors->any())<div class="admin-alert error"><strong>Le formulaire contient une erreur.</strong><span>{{ $errors->first() }}</span></div>@endif
                    <div class="entity-field-grid two">
                        <div class="entity-field"><label for="libelle">Nom de la matière <em>Obligatoire</em></label><input id="libelle" name="libelle" type="text" value="{{ old('libelle', $matiere?->libelle) }}" placeholder="Ex. Mathématiques" required autofocus>@error('libelle')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field"><label for="app_name">Identifiant dans l’application <em>Obligatoire</em></label><input id="app_name" name="app_name" type="text" value="{{ old('app_name', $matiere?->app_name) }}" placeholder="Ex. mathematiques" required><small>Nom réservé utilisé pour les associations internes.</small>@error('app_name')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field full"><label for="description">Description <span>Facultatif</span></label><textarea id="description" name="description" rows="5" placeholder="Présentez le contenu de cette matière…">{{ old('description', $matiere?->description) }}</textarea>@error('description')<span class="field-error">{{ $message }}</span>@enderror</div>
                    </div>
                </article>
                <div class="form-actions-card entity-form-actions"><a href="{{ route('matiere.index') }}" class="admin-button secondary">Annuler</a><button type="submit" class="admin-button primary">{{ $isEdit ? 'Enregistrer les modifications' : 'Créer la matière' }}</button></div>
            </form>
        </div>
        <aside class="entity-form-side">
            <article class="admin-panel form-help-panel"><span class="form-help-icon">MT</span><h2>Bon à savoir</h2><p>L’identifiant interne permet à l’application mobile de reconnaître la matière de manière stable.</p><ul><li>Le nom et l’identifiant doivent être uniques.</li><li>Utilisez un identifiant court et sans ambiguïté.</li><li>La description aide les administrateurs à distinguer les contenus.</li></ul></article>
            @if($isEdit)<article class="admin-panel danger-zone"><p class="admin-eyebrow">Zone sensible</p><h2>Supprimer la matière</h2><p>Vérifiez qu’aucun cours important ne dépend encore de cette matière.</p><form action="{{ route('matiere.destroy', $matiere) }}" method="POST">@csrf @method('DELETE')<button type="submit" class="admin-button danger" onclick="return confirm('Voulez-vous vraiment supprimer cette matière ?')">Supprimer définitivement</button></form></article>@endif
        </aside>
    </div>
</section>
@endsection
