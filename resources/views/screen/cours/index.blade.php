@extends('nav')

@section('title', 'Cours')

@section('content')
    @php
        $columns = [
            ['key' => 'name', 'label' => 'Cours', 'type' => 'identity', 'secondaryKey' => 'subject'],
            ['key' => 'className', 'label' => 'Classe'],
            ['key' => 'category', 'label' => 'Catégorie'],
            ['key' => 'status', 'label' => 'Accès', 'type' => 'status'],
            ['key' => 'author', 'label' => 'Publié par'],
            ['key' => 'createdAt', 'label' => 'Ajouté le'],
        ];
        $items = $cours->map(fn ($course) => [
            'id' => $course->id,
            'name' => $course->libelle,
            'description' => $course->description,
            'subject' => $course->matiere?->libelle,
            'className' => $course->classe?->libelle,
            'category' => $course->categorie?->libelle,
            'status' => $course->open ? 'Gratuit' : 'Abonnement',
            'author' => trim(($course->user?->name ?? '').' '.($course->user?->last_name ?? '')) ?: 'Administrateur',
            'createdAt' => $course->created_at?->format('d/m/Y'),
            'videoUrl' => $course->video_url,
            'editUrl' => route('cours.edit', $course),
            'details' => [
                ['title' => 'Informations pédagogiques', 'fields' => [
                    ['label' => 'Titre', 'value' => $course->libelle],
                    ['label' => 'Description', 'value' => $course->description],
                    ['label' => 'Matière', 'value' => $course->matiere?->libelle],
                    ['label' => 'Classe', 'value' => $course->classe?->libelle],
                    ['label' => 'Catégorie', 'value' => $course->categorie?->libelle],
                ]],
                ['title' => 'Publication', 'fields' => [
                    ['label' => 'Type d’accès', 'value' => $course->open ? 'Gratuit' : 'Abonnement', 'type' => 'status'],
                    ['label' => 'Publié par', 'value' => trim(($course->user?->name ?? '').' '.($course->user?->last_name ?? ''))],
                    ['label' => 'Créé le', 'value' => $course->created_at?->format('d/m/Y à H:i')],
                ]],
            ],
        ])->values();
    @endphp
    <x-admin.data-table eyebrow="Bibliothèque pédagogique" title="Cours" description="Gérez le catalogue vidéo, sa classification et ses droits d’accès." :columns="$columns" :items="$items" :paginator="$cours" :create-url="route('cours.create')" create-label="Nouveau cours" search-placeholder="Titre, matière, classe ou catégorie…" />
@endsection
