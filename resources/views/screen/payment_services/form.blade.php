@extends('nav')

@php($isEdit = filled($service->id))
@section('title', $isEdit ? 'Modifier le service de paiement' : 'Nouveau service de paiement')

@section('content')
<section class="admin-page entity-form-page">
    <div class="admin-page-heading">
        <div>
            <p class="admin-eyebrow">Configuration des paiements</p>
            <h1>{{ $isEdit ? 'Modifier '.$service->title : 'Ajouter un service de paiement' }}</h1>
            <p>Définissez le libellé présenté au client, le fournisseur et le sens de transaction.</p>
        </div>
        <a href="{{ route('admin.payment-services.index') }}" class="admin-button secondary">Retour aux services</a>
    </div>

    <div class="entity-form-grid">
        <div class="entity-form-main">
            <form method="POST" enctype="multipart/form-data" action="{{ $isEdit ? route('admin.payment-services.update', $service) : route('admin.payment-services.store') }}">
                @csrf
                @if($isEdit) @method('PUT') @endif
                <article class="admin-panel form-panel">
                    <div class="form-section-heading"><span>01</span><div><h2>Présentation dans l’application</h2><p>Ces informations complètent le contrat API existant sans retirer aucun champ.</p></div></div>
                    @if ($errors->any())<div class="admin-alert error"><strong>Enregistrement impossible.</strong><span>{{ $errors->first() }}</span></div>@endif
                    <div class="entity-field-grid two">
                        <div class="entity-field"><label for="title">Nom du service <em>Obligatoire</em></label><input id="title" name="title" type="text" value="{{ old('title', $service->title) }}" placeholder="Ex. Dépôt MTN Mobile Money" required autofocus>@error('title')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field"><label for="subtitle">Sous-titre <em>Obligatoire</em></label><input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle', $service->subtitle) }}" placeholder="Ex. Paiement instantané et sécurisé" required>@error('subtitle')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field full"><label for="description">Description <span>Facultatif</span></label><textarea id="description" name="description" rows="4" placeholder="Expliquez ce moyen de paiement…">{{ old('description', $service->description) }}</textarea>@error('description')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field full"><label for="image">Image du service <span>Facultatif</span></label><input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp,image/svg+xml"><small>PNG, JPG, WEBP ou SVG — 5 Mo maximum.</small>@error('image')<span class="field-error">{{ $message }}</span>@enderror</div>
                    </div>
                    @if($service->image_url)<div class="entity-current-image"><img src="{{ $service->image_url }}" alt="Image de {{ $service->title }}"><div><strong>Image actuelle</strong><span>Une nouvelle image la remplacera.</span></div></div>@endif
                </article>

                <article class="admin-panel form-panel payment-routing-panel">
                    <div class="form-section-heading"><span>02</span><div><h2>Routage et comportement</h2><p>Associez ce service à une passerelle et précisez son usage.</p></div></div>
                    <div class="entity-field-grid two">
                        <div class="entity-field"><label for="payment_provider_id">Fournisseur <em>Obligatoire</em></label><select id="payment_provider_id" name="payment_provider_id" required><option value="">Sélectionner un fournisseur</option>@foreach($providers as $provider)<option value="{{ $provider->id }}" @selected((string) old('payment_provider_id', $service->payment_provider_id) === (string) $provider->id)>{{ $provider->name }} — {{ $provider->code }}{{ $provider->is_active ? ' (actif)' : '' }}</option>@endforeach</select>@error('payment_provider_id')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field"><label for="sens">Sens de transaction <em>Obligatoire</em></label><select id="sens" name="sens" required><option value="IN" @selected(old('sens', $service->sens) === 'IN')>Encaissement (IN)</option><option value="OUT" @selected(old('sens', $service->sens) === 'OUT')>Décaissement (OUT)</option></select>@error('sens')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field"><label for="subscription_id">ID service fournisseur <span>Facultatif</span></label><input id="subscription_id" name="subscription_id" type="number" min="1" value="{{ old('subscription_id', $service->subscription_id) }}" placeholder="Ex. 12"><small>Pour MundiPay, utilisez l’identifiant de souscription fourni pour ce service.</small>@error('subscription_id')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field"><label for="status">Statut technique <em>Obligatoire</em></label><input id="status" name="status" type="number" value="{{ old('status', $service->status ?? 1) }}" required><small>Champ historique conservé pour la compatibilité du client.</small>@error('status')<span class="field-error">{{ $message }}</span>@enderror</div>
                        <div class="entity-field full"><label for="reg_exp">Expression régulière <span>Facultatif</span></label><input id="reg_exp" name="reg_exp" type="text" value="{{ old('reg_exp', $service->reg_exp) }}" placeholder="Ex. ^6(5|7|8|9)[0-9]{7}$"><small>Utilisée pour reconnaître ou valider les numéros compatibles.</small>@error('reg_exp')<span class="field-error">{{ $message }}</span>@enderror</div>
                    </div>
                    <div class="entity-status-card">
                        <div><strong>Service disponible</strong><small>Un service inactif reste enregistré mais n’est plus retourné par la route publique historique.</small></div>
                        <label class="entity-switch"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $service->is_active) ? 'checked' : '' }}><span></span><em>Actif</em></label>
                    </div>
                </article>

                <div class="form-actions-card entity-form-actions"><a href="{{ route('admin.payment-services.index') }}" class="admin-button secondary">Annuler</a><button type="submit" class="admin-button primary">{{ $isEdit ? 'Enregistrer les modifications' : 'Créer le service' }}</button></div>
            </form>
        </div>
        <aside class="entity-form-side">
            <article class="admin-panel form-help-panel"><span class="form-help-icon">PAY</span><h2>Compatibilité préservée</h2><p>Le service garde tous ses champs historiques. Le fournisseur et l’URL publique de l’image sont ajoutés au retour.</p><ul><li>L’image n’est jamais obligatoire.</li><li>Le sens IN encaisse, OUT décaisse.</li><li>Seuls les services actifs sont visibles côté client.</li></ul></article>
            @if($isEdit)<article class="admin-panel danger-zone"><p class="admin-eyebrow">Zone sensible</p><h2>Désactiver le service</h2><p>Le service ne sera plus proposé aux clients, mais toutes ses données seront conservées.</p><form action="{{ route('admin.payment-services.destroy', $service) }}" method="POST">@csrf @method('DELETE')<button type="submit" class="admin-button danger" onclick="return confirm('Voulez-vous désactiver ce service ?')">Désactiver le service</button></form></article>@endif
        </aside>
    </div>
</section>
@endsection
