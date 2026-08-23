@extends('nav')

@section('title', 'Paiements')

@section('content')
    @php
        $columns = [
            ['key' => 'name', 'label' => 'Client', 'type' => 'identity', 'secondaryKey' => 'phone'],
            ['key' => 'amount', 'label' => 'Montant', 'type' => 'currency', 'emphasis' => true],
            ['key' => 'category', 'label' => 'Catégorie'],
            ['key' => 'quantity', 'label' => 'Codes', 'type' => 'number'],
            ['key' => 'status', 'label' => 'Statut', 'type' => 'status'],
            ['key' => 'createdAt', 'label' => 'Demandé le'],
        ];
    @endphp

    <x-admin.data-table
        eyebrow="Monétisation"
        title="Paiements"
        description="Contrôlez les paiements, les transactions fournisseur, les codes et les tentatives de réconciliation."
        :columns="$columns"
        :items="$items"
        :paginator="$payments"
        search-placeholder="Client, téléphone, transaction, provider ou catégorie…"
    />
@endsection
