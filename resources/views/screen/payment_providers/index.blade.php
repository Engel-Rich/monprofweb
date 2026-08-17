@extends('nav')

@section('title', 'Fournisseurs de paiement')

@section('content')
    @php
        $columns = [
            ['key' => 'name', 'label' => 'Fournisseur', 'type' => 'imageIdentity', 'secondaryKey' => 'code', 'imageKey' => 'imageUrl'],
            ['key' => 'status', 'label' => 'État', 'type' => 'status'],
            ['key' => 'servicesCount', 'label' => 'Services', 'type' => 'number'],
            ['key' => 'strategy', 'label' => 'Intégration'],
            ['key' => 'updatedAt', 'label' => 'Mis à jour'],
        ];
        $filters = [[
            'key' => 'strategy',
            'label' => 'Intégration technique',
            'allLabel' => 'Toutes les intégrations',
            'options' => [
                ['value' => 'Configurée', 'label' => 'Configurée'],
                ['value' => 'Non configurée', 'label' => 'Non configurée'],
            ],
        ]];
        $items = $providers->map(function ($provider) {
            $supported = \App\Services\Payments\PaymentFactory::supports($provider->code);
            return [
                'id' => $provider->id,
                'name' => $provider->name,
                'code' => $provider->code,
                'imageUrl' => $provider->image_url,
                'status' => $provider->is_active ? 'Actif' : 'Inactif',
                'servicesCount' => $provider->services_count,
                'strategy' => $supported ? 'Configurée' : 'Non configurée',
                'updatedAt' => $provider->updated_at?->format('d/m/Y à H:i'),
                'editUrl' => route('admin.payment-providers.edit', $provider),
                'details' => [[
                    'title' => 'Identification',
                    'fields' => [
                        ['label' => 'Nom', 'value' => $provider->name],
                        ['label' => 'Code', 'value' => $provider->code],
                        ['label' => 'État', 'value' => $provider->is_active ? 'Actif' : 'Inactif', 'type' => 'status'],
                        ['label' => 'Stratégie disponible', 'value' => $supported ? 'Oui' : 'Non', 'type' => 'boolean'],
                    ],
                ], [
                    'title' => 'Utilisation',
                    'fields' => [
                        ['label' => 'Services associés', 'value' => $provider->services_count, 'type' => 'number'],
                        ['label' => 'Créé le', 'value' => $provider->created_at?->format('d/m/Y à H:i')],
                        ['label' => 'Mis à jour le', 'value' => $provider->updated_at?->format('d/m/Y à H:i')],
                    ],
                ]],
            ];
        })->values();
    @endphp

    <x-admin.data-table
        eyebrow="Configuration des paiements"
        title="Fournisseurs de paiement"
        description="Choisissez la passerelle active utilisée pour initier les transactions MonProf."
        :columns="$columns"
        :items="$items"
        :filters="$filters"
        :paginator="$providers"
        :create-url="route('admin.payment-providers.create')"
        create-label="Nouveau fournisseur"
        search-placeholder="Rechercher un nom ou un code…"
    />
@endsection
