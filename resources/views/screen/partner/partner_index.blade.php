@extends('nav')

@section('title', 'Partenaires')

@section('content')
    @php
        $columns = [
            ['key' => 'name', 'label' => 'Partenaire', 'type' => 'identity', 'secondaryKey' => 'email'],
            ['key' => 'phone', 'label' => 'Téléphone'],
            ['key' => 'createdAt', 'label' => 'Ajouté le'],
        ];
        $items = $partners->map(function ($partner) {
            $name = trim(($partner->name ?? '').' '.($partner->last_name ?? '')) ?: 'Partenaire';
            return [
                'id' => $partner->id,
                'name' => $name,
                'email' => $partner->email,
                'phone' => $partner->phone,
                'createdAt' => $partner->created_at?->format('d/m/Y'),
                'details' => [['title' => 'Compte partenaire', 'fields' => [
                    ['label' => 'Nom complet', 'value' => $name],
                    ['label' => 'Adresse email', 'value' => $partner->email, 'type' => 'email'],
                    ['label' => 'Téléphone', 'value' => $partner->phone, 'type' => 'phone'],
                    ['label' => 'Ajouté le', 'value' => $partner->created_at?->format('d/m/Y à H:i')],
                ]]],
            ];
        })->values();
    @endphp
    <x-admin.data-table eyebrow="Réseau" title="Partenaires" description="Gérez les comptes partenaires autorisés à accéder aux statistiques." :columns="$columns" :items="$items" :create-url="route('partner.add')" create-label="Nouveau partenaire" />
@endsection
