@extends('nav')

@section('title', 'Suggestions')

@section('content')
    @php
        $columns = [
            ['key' => 'author', 'label' => 'Expéditeur', 'type' => 'identity', 'secondaryKey' => 'title'],
            ['key' => 'body', 'label' => 'Suggestion', 'truncate' => true],
            ['key' => 'createdAt', 'label' => 'Reçue le'],
        ];
        $items = $suggestion->map(fn ($message) => [
            'id' => $message->id,
            'author' => $message->user?->email ?: 'Utilisateur anonyme',
            'title' => $message->title ?: 'Suggestion sans titre',
            'body' => $message->body,
            'createdAt' => $message->created_at?->format('d/m/Y H:i'),
            'details' => [['title' => 'Suggestion reçue', 'fields' => [
                ['label' => 'Titre', 'value' => $message->title],
                ['label' => 'Message', 'value' => $message->body],
                ['label' => 'Expéditeur', 'value' => $message->user?->email ?: 'Anonyme', 'type' => 'email'],
                ['label' => 'Date d’envoi', 'value' => $message->created_at?->format('d/m/Y à H:i:s')],
            ]]],
        ])->values();
    @endphp
    <x-admin.data-table eyebrow="Retours utilisateurs" title="Suggestions" description="Centralisez les idées et remarques envoyées depuis l’application." :columns="$columns" :items="$items" :paginator="$suggestion" />
@endsection
