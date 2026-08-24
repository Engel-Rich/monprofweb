@extends('nav')

@section('title', 'Services de paiement')

@section('content')
    @php
        $columns = [
            ['key' => 'name', 'label' => 'Service', 'type' => 'imageIdentity', 'secondaryKey' => 'subtitle', 'imageKey' => 'imageUrl'],
            ['key' => 'providerName', 'label' => 'Fournisseur'],
            ['key' => 'directionLabel', 'label' => 'Sens'],
            ['key' => 'status', 'label' => 'État', 'type' => 'status'],
            ['key' => 'updatedAt', 'label' => 'Mis à jour'],
        ];
        $filters = [
            [
                'key' => 'providerId',
                'label' => 'Fournisseur',
                'allLabel' => 'Tous les fournisseurs',
                'options' => $providers->map(fn ($provider) => ['value' => (string) $provider->id, 'label' => $provider->name])->values(),
            ],
            [
                'key' => 'direction',
                'label' => 'Sens de transaction',
                'allLabel' => 'Tous les sens',
                'options' => [
                    ['value' => 'IN', 'label' => 'Encaissement'],
                    ['value' => 'OUT', 'label' => 'Décaissement'],
                ],
            ],
        ];
        $items = $services->map(fn ($service) => [
            'id' => $service->id,
            'name' => $service->title,
            'subtitle' => $service->subtitle,
            'description' => $service->description,
            'imageUrl' => $service->image_url,
            'providerId' => (string) $service->payment_provider_id,
            'providerName' => $service->provider?->name ?? 'Non rattaché',
            'providerCode' => $service->provider?->code,
            'direction' => $service->sens,
            'directionLabel' => match($service->sens) { 'IN' => 'Encaissement', 'OUT' => 'Décaissement', default => 'Non défini' },
            'status' => $service->is_active ? 'Actif' : 'Inactif',
            'updatedAt' => $service->updated_at?->format('d/m/Y à H:i'),
            'editUrl' => route('admin.payment-services.edit', $service),
            'details' => [[
                'title' => 'Service de paiement',
                'fields' => [
                    ['label' => 'Titre', 'value' => $service->title],
                    ['label' => 'Sous-titre', 'value' => $service->subtitle],
                    ['label' => 'Description', 'value' => $service->description],
                    ['label' => 'État', 'value' => $service->is_active ? 'Actif' : 'Inactif', 'type' => 'status'],
                ],
            ], [
                'title' => 'Routage',
                'fields' => [
                    ['label' => 'Fournisseur', 'value' => $service->provider?->name ?? 'Non rattaché'],
                    ['label' => 'Code fournisseur', 'value' => $service->provider?->code],
                    ['label' => 'Sens', 'value' => match($service->sens) { 'IN' => 'Encaissement (IN)', 'OUT' => 'Décaissement (OUT)', default => 'Non défini' }],
                    ['label' => 'ID service fournisseur', 'value' => $service->provider_service_id],
                    ['label' => 'Expression régulière', 'value' => $service->reg_exp],
                    ['label' => 'Statut technique', 'value' => $service->status],
                ],
            ]],
        ])->values();
    @endphp

    <x-admin.data-table
        eyebrow="Configuration des paiements"
        title="Services de paiement"
        description="Gérez les moyens d’encaissement et de décaissement proposés aux utilisateurs."
        :columns="$columns"
        :items="$items"
        :filters="$filters"
        :paginator="$services"
        :create-url="route('admin.payment-services.create')"
        create-label="Nouveau service"
        search-placeholder="Rechercher un service, un fournisseur…"
    />
@endsection
