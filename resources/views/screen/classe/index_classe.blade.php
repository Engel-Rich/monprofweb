@extends('nav')

@section('title', 'Classes')

@section('content')
    @php
        $columns = [
            ['key' => 'name', 'label' => 'Classe', 'type' => 'identity', 'secondaryKey' => 'shortName'],
            ['key' => 'description', 'label' => 'Description', 'truncate' => true],
            ['key' => 'createdAt', 'label' => 'Créée le'],
        ];
        $items = $classes->map(fn ($classe) => [
            'id' => $classe->id,
            'name' => $classe->libelle,
            'shortName' => $classe->short_name,
            'description' => $classe->description,
            'createdAt' => $classe->created_at?->format('d/m/Y'),
            'editUrl' => route('classe.edit', $classe),
            'details' => [[
                'title' => 'Informations de la classe',
                'fields' => [
                    ['label' => 'Nom complet', 'value' => $classe->libelle],
                    ['label' => 'Abréviation', 'value' => $classe->short_name],
                    ['label' => 'Description', 'value' => $classe->description],
                    ['label' => 'Créée le', 'value' => $classe->created_at?->format('d/m/Y à H:i')],
                ],
            ]],
        ])->values();
    @endphp
    <x-admin.data-table eyebrow="Scolarité" title="Classes" description="Organisez les niveaux scolaires disponibles sur MonProf." :columns="$columns" :items="$items" :paginator="$classes" :create-url="route('classe.create')" create-label="Nouvelle classe" />
@endsection
