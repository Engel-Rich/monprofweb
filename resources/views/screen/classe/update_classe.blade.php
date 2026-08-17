@extends('nav')

@section('title', 'Modifier la classe')

@section('content')
<section class="admin-page entity-form-page">
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Organisation pédagogique</p>
            <h1>Modifier {{ $classe->libelle }}</h1>
            <p>Mettez à jour les informations et les matières rattachées à cette classe.</p>
        </div>
        <a href="{{ route('classe.index') }}" class="admin-button secondary">Retour aux classes</a>
    </div>

    <div class="entity-form-grid class-edit-grid">
        <div class="entity-form-main">
            <form method="POST" action="{{ route('classe.update', $classe) }}">
                @csrf
                @method('PUT')
                <article class="admin-panel form-panel">
                    <div class="form-section-heading"><span>01</span><div><h2>Informations de la classe</h2><p>Modifiez uniquement les informations qui doivent évoluer.</p></div></div>
                    @if ($errors->any())<div class="admin-alert error"><strong>Modification impossible.</strong><span>{{ $errors->first() }}</span></div>@endif
                    <div class="entity-field-grid two">
                        <div class="entity-field"><label for="libelle">Nom de la classe <em>Obligatoire</em></label><input id="libelle" name="libelle" type="text" value="{{ old('libelle', $classe->libelle) }}" required autofocus>@error('libelle')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field"><label for="short_name">Abréviation <em>Obligatoire</em></label><input id="short_name" name="short_name" type="text" value="{{ old('short_name', $classe->short_name) }}" required>@error('short_name')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field full"><label for="description">Description <span>Facultatif</span></label><textarea id="description" name="description" rows="5">{{ old('description', $classe->description) }}</textarea>@error('description')<span class="field-error">{{ $message }}</span>@enderror</div>
                    </div>
                </article>
                <div class="form-actions-card entity-form-actions"><a href="{{ route('classe.index') }}" class="admin-button secondary">Annuler</a><button type="submit" class="admin-button primary">Enregistrer les modifications</button></div>
            </form>

            <article class="admin-panel class-subject-panel">
                <div class="form-section-heading"><span>02</span><div><h2>Matières associées</h2><p>Ajoutez ou retirez les matières disponibles pour cette classe.</p></div></div>
                @livewire('manage-matiere-to-classe', ['classe' => $classe])
            </article>
        </div>

        <aside class="entity-form-side">
            <article class="admin-panel form-help-panel"><span class="form-help-icon">{{ strtoupper(substr($classe->short_name, 0, 2)) }}</span><h2>{{ $classe->libelle }}</h2><p>Les modifications de matières sont appliquées immédiatement.</p><ul><li>{{ $classe->matieres->count() }} matière(s) associée(s).</li><li>Les cours existants ne sont pas supprimés.</li></ul></article>
            <article class="admin-panel danger-zone"><p class="admin-eyebrow">Zone sensible</p><h2>Supprimer la classe</h2><p>Cette action est définitive et peut affecter les contenus associés.</p><form action="{{ route('classe.destroy', $classe) }}" method="POST">@csrf @method('DELETE')<button type="submit" class="admin-button danger" onclick="return confirm('Voulez-vous vraiment supprimer cette classe ?')">Supprimer définitivement</button></form></article>
        </aside>
    </div>
</section>
@endsection
