@extends('nav')

@section('title', 'Catégories')

@section('content')
    @php
        $columns = [
            ['key' => 'name', 'label' => 'Catégorie', 'type' => 'identity', 'secondaryKey' => 'description'],
            ['key' => 'price', 'label' => 'Tarif', 'type' => 'currency', 'emphasis' => true],
            ['key' => 'status', 'label' => 'Statut', 'type' => 'status'],
            ['key' => 'createdAt', 'label' => 'Créée le'],
        ];
        $items = $categories->map(fn ($category) => [
            'id' => $category->id,
            'name' => $category->libelle,
            'description' => $category->description,
            'price' => (float) $category->prix,
            'status' => $category->status ? 'Active' : 'Inactive',
            'createdAt' => $category->created_at?->format('d/m/Y'),
            'editUrl' => route('categorie.edit', $category),
            'highlight' => ['label' => 'Prix de l’offre', 'value' => (float) $category->prix, 'type' => 'currency', 'helper' => $category->description],
            'details' => [['title' => 'Configuration', 'fields' => [
                ['label' => 'Libellé', 'value' => $category->libelle],
                ['label' => 'Statut', 'value' => $category->status ? 'Active' : 'Inactive', 'type' => 'status'],
                ['label' => 'Description', 'value' => $category->description],
                ['label' => 'Créée le', 'value' => $category->created_at?->format('d/m/Y à H:i')],
            ]]],
        ])->values();
    @endphp
    <x-admin.data-table eyebrow="Catalogue" title="Catégories" description="Configurez les offres, leurs tarifs et leur visibilité." :columns="$columns" :items="$items" :paginator="$categories" :create-url="route('categorie.create')" create-label="Nouvelle catégorie" />
@endsection
