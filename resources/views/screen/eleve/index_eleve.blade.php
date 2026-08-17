@extends('nav')

@section('title', 'Élèves')

@section('content')
    @php
        $columns = [
            ['key' => 'name', 'label' => 'Élève', 'type' => 'identity', 'secondaryKey' => 'email'],
            ['key' => 'className', 'label' => 'Classe'],
            ['key' => 'school', 'label' => 'Établissement', 'truncate' => true],
            ['key' => 'phone', 'label' => 'Téléphone'],
            ['key' => 'createdAt', 'label' => 'Inscrit le'],
        ];
        $items = $eleves->map(function ($student) {
            $name = trim(($student->user?->name ?? '').' '.($student->user?->last_name ?? '')) ?: 'Élève sans compte';
            return [
                'id' => $student->id,
                'name' => $name,
                'email' => $student->user?->email,
                'phone' => $student->user?->phone,
                'className' => $student->classe?->libelle,
                'school' => $student->etablissement,
                'gender' => $student->sexe,
                'createdAt' => $student->created_at?->format('d/m/Y'),
                'details' => [
                    ['title' => 'Compte', 'fields' => [
                        ['label' => 'Nom complet', 'value' => $name],
                        ['label' => 'Adresse email', 'value' => $student->user?->email, 'type' => 'email'],
                        ['label' => 'Téléphone', 'value' => $student->user?->phone, 'type' => 'phone'],
                    ]],
                    ['title' => 'Scolarité', 'fields' => [
                        ['label' => 'Classe', 'value' => $student->classe?->libelle],
                        ['label' => 'Établissement', 'value' => $student->etablissement],
                        ['label' => 'Sexe', 'value' => $student->sexe],
                        ['label' => 'Inscrit le', 'value' => $student->created_at?->format('d/m/Y à H:i')],
                    ]],
                ],
            ];
        })->values();
    @endphp
    <x-admin.data-table eyebrow="Utilisateurs" title="Élèves" description="Consultez les comptes élèves et leurs informations scolaires." :columns="$columns" :items="$items" :paginator="$eleves" search-placeholder="Nom, email, téléphone ou classe…" />
@endsection
