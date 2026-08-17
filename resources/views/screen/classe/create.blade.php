@extends('nav')

@section('title', 'Nouvelle classe')

@section('content')
<section class="admin-page entity-form-page">
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Organisation pédagogique</p>
            <h1>Ajouter une classe</h1>
            <p>Créez le niveau qui permettra de structurer les matières, les cours et les élèves.</p>
        </div>
        <a href="{{ route('classe.index') }}" class="admin-button secondary">Retour aux classes</a>
    </div>

    <form method="POST" action="{{ route('classe.store') }}" class="entity-form-grid">
        @csrf
        <div class="entity-form-main">
            <article class="admin-panel form-panel">
                <div class="form-section-heading"><span>01</span><div><h2>Informations de la classe</h2><p>Les informations visibles par les administrateurs et les élèves.</p></div></div>

                @if ($errors->any())
                    <div class="admin-alert error"><strong>Le formulaire contient une erreur.</strong><span>{{ $errors->first() }}</span></div>
                @endif

                <div class="entity-field-grid two">
                    <div class="entity-field">
                        <label for="libelle">Nom de la classe <em>Obligatoire</em></label>
                        <input id="libelle" name="libelle" type="text" value="{{ old('libelle') }}" placeholder="Ex. Terminale C" required autofocus>
                        <small>Utilisez le nom complet affiché dans l’application.</small>
                        @error('libelle')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="entity-field">
                        <label for="short_name">Abréviation <em>Obligatoire</em></label>
                        <input id="short_name" name="short_name" type="text" value="{{ old('short_name') }}" placeholder="Ex. Tle C" required>
                        <small>Une version courte, facile à identifier dans les listes.</small>
                        @error('short_name')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                    <div class="entity-field full">
                        <label for="description">Description <span>Facultatif</span></label>
                        <textarea id="description" name="description" rows="5" placeholder="Décrivez brièvement ce niveau scolaire…">{{ old('description') }}</textarea>
                        @error('description')<span class="field-error">{{ $message }}</span>@enderror
                    </div>
                </div>
            </article>

            <div class="form-actions-card entity-form-actions">
                <a href="{{ route('classe.index') }}" class="admin-button secondary">Annuler</a>
                <button type="submit" class="admin-button primary">Créer la classe</button>
            </div>
        </div>

        <aside class="entity-form-side">
            <article class="admin-panel form-help-panel">
                <span class="form-help-icon">CL</span>
                <h2>Une structure claire</h2>
                <p>La classe sert de point d’entrée pour organiser le catalogue pédagogique MonProf.</p>
                <ul><li>Choisissez un nom compréhensible.</li><li>Gardez une abréviation unique.</li><li>Vous pourrez associer les matières après création.</li></ul>
            </article>
        </aside>
    </form>
</section>
@endsection
