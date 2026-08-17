@extends('nav')

@php($isEdit = $message->exists)
@section('title', $isEdit ? 'Modifier le message' : 'Nouveau message')

@section('content')
<section class="admin-page entity-form-page">
    <div class="admin-page-heading">
        <div><p class="admin-eyebrow">Communication</p><h1>{{ $isEdit ? 'Modifier le message' : 'Nouveau message' }}</h1><p>{{ $isEdit ? 'Corrigez le contenu enregistré sans déclencher une nouvelle notification.' : 'Rédigez une notification claire à destination des utilisateurs MonProf.' }}</p></div>
        <a href="{{ route('messages.index') }}" class="admin-button secondary">Retour aux messages</a>
    </div>

    <form method="POST" action="{{ $isEdit ? route('messages.update', $message) : route('messages.store') }}" class="entity-form-grid">
        @csrf
        @if($isEdit) @method('PUT') @endif
        <div class="entity-form-main">
            <article class="admin-panel form-panel">
                <div class="form-section-heading"><span>01</span><div><h2>Contenu du message</h2><p>Le titre et le texte seront transmis par notification après l’enregistrement.</p></div></div>
                @if ($errors->any())<div class="admin-alert error"><strong>Le message ne peut pas être envoyé.</strong><span>{{ $errors->first() }}</span></div>@endif
                <div class="entity-field-grid">
                    <div class="entity-field"><label for="title">Titre du message <em>Obligatoire</em></label><input id="title" name="title" type="text" value="{{ old('title', $message->title) }}" placeholder="Ex. Nouveaux cours disponibles" required autofocus><small>Commencez par l’information la plus importante.</small>@error('title')<span class="field-error">{{ $message }}</span>@enderror</div>
                    <div class="entity-field"><label for="body">Corps du message <em>Obligatoire</em></label><textarea id="body" name="body" rows="9" placeholder="Rédigez votre message…" required>{{ old('body', $message->body) }}</textarea><small>Utilisez des phrases courtes et indiquez clairement l’action attendue.</small>@error('body')<span class="field-error">{{ $message }}</span>@enderror</div>
                </div>
            </article>
            <div class="form-actions-card entity-form-actions"><a href="{{ route('messages.index') }}" class="admin-button secondary">Annuler</a><button type="submit" class="admin-button primary">{{ $isEdit ? 'Enregistrer les modifications' : 'Enregistrer et envoyer' }}</button></div>
        </div>
        <aside class="entity-form-side">
            <article class="message-delivery-card"><span>{{ $isEdit ? 'MODIFICATION' : 'NOTIFICATION' }}</span><h2>{{ $isEdit ? 'Mise à jour sans rediffusion' : 'Diffusion aux utilisateurs' }}</h2><p>{{ $isEdit ? 'Le contenu enregistré sera corrigé, mais aucune nouvelle notification ne sera envoyée automatiquement.' : 'Après validation, le message est enregistré puis placé dans la file d’envoi Firebase.' }}</p><div><strong>Conseils de rédaction</strong><ul><li>Un titre précis et court.</li><li>Une seule information principale.</li><li>Évitez les données personnelles.</li></ul></div></article>
            <article class="admin-panel form-help-panel compact"><h2>Avant d’envoyer</h2><p>Relisez attentivement le contenu. La logique actuelle ne prévoit pas de modification après la diffusion.</p></article>
        </aside>
    </form>
</section>
@endsection
