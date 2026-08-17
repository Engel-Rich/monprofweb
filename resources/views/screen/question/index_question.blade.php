@extends('nav')

@section('title', 'Questions')

@section('content')
    @php
        $columns = [
            ['key' => 'title', 'label' => 'Question', 'type' => 'identity', 'secondaryKey' => 'student'],
            ['key' => 'subject', 'label' => 'Matière'],
            ['key' => 'className', 'label' => 'Classe'],
            ['key' => 'category', 'label' => 'Catégorie'],
            ['key' => 'status', 'label' => 'Statut', 'type' => 'status'],
            ['key' => 'createdAt', 'label' => 'Reçue le'],
        ];
        $items = $questions->map(function ($question) {
            $student = trim(($question->eleve?->user?->name ?? '').' '.($question->eleve?->user?->last_name ?? '')) ?: 'Élève inconnu';
            $status = $question->reponse ? 'Répondue' : 'En attente';
            return [
                'id' => $question->id,
                'title' => $question->titre,
                'name' => $question->titre,
                'student' => $student,
                'description' => $question->description,
                'subject' => $question->matiere?->libelle,
                'className' => $question->classe?->libelle,
                'category' => $question->categorie?->libelle,
                'status' => $status,
                'createdAt' => $question->created_at?->format('d/m/Y H:i'),
                'imageUrl' => $question->image_url,
                'actionUrl' => route('question.show', $question),
                'actionLabel' => $question->reponse ? 'Consulter la réponse' : 'Répondre',
                'details' => [
                    ['title' => 'Demande', 'fields' => [
                        ['label' => 'Question', 'value' => $question->description],
                        ['label' => 'Statut', 'value' => $status, 'type' => 'status'],
                        ['label' => 'Élève', 'value' => $student],
                        ['label' => 'Reçue le', 'value' => $question->created_at?->format('d/m/Y à H:i:s')],
                    ]],
                    ['title' => 'Contexte pédagogique', 'fields' => [
                        ['label' => 'Matière', 'value' => $question->matiere?->libelle],
                        ['label' => 'Classe', 'value' => $question->classe?->libelle],
                        ['label' => 'Catégorie', 'value' => $question->categorie?->libelle],
                    ]],
                ],
            ];
        })->values();
    @endphp
    <x-admin.data-table eyebrow="Support pédagogique" title="Questions des élèves" description="Priorisez les demandes et ouvrez leur détail avant de répondre." :columns="$columns" :items="$items" :paginator="$questions" search-placeholder="Titre, élève, matière ou classe…" />
@endsection
