@extends('nav')

@section('title', isset($cour) ? 'Modifier le cours' : 'Nouveau cours')

@section('content')
    @php
        $courseData = isset($cour) ? [
            'id' => $cour->id,
            'libelle' => $cour->libelle,
            'description' => $cour->description,
            'matieres_id' => $cour->matieres_id,
            'classe_id' => $cour->classe_id,
            'categorie_id' => $cour->categorie_id,
            'open' => (int) $cour->open,
        ] : [];
    @endphp

    <course-upload-form
        action="{{ isset($cour) ? route('cours.update', $cour) : route('cours.store') }}"
        method="{{ isset($cour) ? 'PUT' : 'POST' }}"
        csrf="{{ csrf_token() }}"
        :course="{{ Js::from($courseData) }}"
        :subjects="{{ Js::from($matieres->map->only(['id', 'libelle'])->values()) }}"
        :classes="{{ Js::from($classes->map->only(['id', 'libelle'])->values()) }}"
        :categories="{{ Js::from($categories->map->only(['id', 'libelle'])->values()) }}"
        index-url="{{ route('cours.index') }}"
        delete-url="{{ isset($cour) ? route('cours.destroy', $cour) : '' }}"
    ></course-upload-form>
@endsection
