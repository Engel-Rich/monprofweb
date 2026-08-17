@extends('nav')

@section('title', 'Enseignants')

@section('content')
    @php
        $columns = [
            ['key' => 'name', 'label' => 'Enseignant', 'type' => 'identity', 'secondaryKey' => 'email'],
            ['key' => 'subject', 'label' => 'Matière principale'],
            ['key' => 'phone', 'label' => 'Téléphone'],
            ['key' => 'createdAt', 'label' => 'Ajouté le'],
        ];
        $items = $profs->map(function ($teacher) {
            $name = trim(($teacher->user?->name ?? '').' '.($teacher->user?->last_name ?? '')) ?: 'Compte enseignant';
            return [
                'id' => $teacher->id,
                'name' => $name,
                'email' => $teacher->user?->email,
                'phone' => $teacher->user?->phone,
                'subject' => $teacher->matiere?->libelle,
                'createdAt' => $teacher->created_at?->format('d/m/Y'),
                'details' => [
                    ['title' => 'Coordonnées', 'fields' => [
                        ['label' => 'Nom complet', 'value' => $name],
                        ['label' => 'Email', 'value' => $teacher->user?->email, 'type' => 'email'],
                        ['label' => 'Téléphone', 'value' => $teacher->user?->phone, 'type' => 'phone'],
                    ]],
                    ['title' => 'Affectation', 'fields' => [
                        ['label' => 'Matière principale', 'value' => $teacher->matiere?->libelle],
                        ['label' => 'Ajouté le', 'value' => $teacher->created_at?->format('d/m/Y à H:i')],
                    ]],
                ],
            ];
        })->values();
    @endphp
    <x-admin.data-table eyebrow="Équipe pédagogique" title="Enseignants" description="Retrouvez les enseignants et leurs matières de référence." :columns="$columns" :items="$items" :paginator="$profs" :create-url="route('professeur.create')" create-label="Nouvel enseignant" />
@endsection
