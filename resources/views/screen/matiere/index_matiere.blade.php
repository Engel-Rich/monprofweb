@extends('nav')

@section('title', 'Matières')

@section('content')
    @php
        $columns = [
            ['key' => 'name', 'label' => 'Matière', 'type' => 'identity', 'secondaryKey' => 'appName'],
            ['key' => 'description', 'label' => 'Description', 'truncate' => true],
            ['key' => 'createdAt', 'label' => 'Créée le'],
        ];
        $items = $matieres->map(fn ($matiere) => [
            'id' => $matiere->id,
            'name' => $matiere->libelle,
            'appName' => $matiere->app_name ?: 'Nom public non défini',
            'description' => $matiere->description,
            'createdAt' => $matiere->created_at?->format('d/m/Y'),
            'editUrl' => route('matiere.edit', $matiere),
            'details' => [['title' => 'Informations de la matière', 'fields' => [
                ['label' => 'Libellé', 'value' => $matiere->libelle],
                ['label' => 'Nom dans l’application', 'value' => $matiere->app_name],
                ['label' => 'Description', 'value' => $matiere->description],
                ['label' => 'Créée le', 'value' => $matiere->created_at?->format('d/m/Y à H:i')],
            ]]],
        ])->values();
    @endphp
    <x-admin.data-table eyebrow="Contenu" title="Matières" description="Gérez les disciplines utilisées pour classer les contenus pédagogiques." :columns="$columns" :items="$items" :paginator="$matieres" :create-url="route('matiere.create')" create-label="Nouvelle matière" />
@endsection
