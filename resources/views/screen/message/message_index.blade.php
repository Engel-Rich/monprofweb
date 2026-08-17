@extends('nav')

@section('title', 'Messages')

@section('content')
    @php
        $columns = [
            ['key' => 'title', 'label' => 'Message', 'type' => 'identity', 'secondaryKey' => 'excerpt'],
            ['key' => 'createdAt', 'label' => 'Envoyé le'],
        ];
        $items = $messages->map(fn ($message) => [
            'id' => $message->id,
            'title' => $message->title,
            'name' => $message->title,
            'excerpt' => \Illuminate\Support\Str::limit($message->body, 90),
            'createdAt' => $message->created_at?->format('d/m/Y H:i'),
            'details' => [['title' => 'Contenu du message', 'fields' => [
                ['label' => 'Titre', 'value' => $message->title],
                ['label' => 'Message', 'value' => $message->body],
                ['label' => 'Date d’envoi', 'value' => $message->created_at?->format('d/m/Y à H:i:s')],
            ]]],
        ])->values();
    @endphp
    <x-admin.data-table eyebrow="Communication" title="Messages" description="Consultez l’historique des communications envoyées aux utilisateurs." :columns="$columns" :items="$items" :paginator="$messages" :create-url="route('messages.create')" create-label="Nouveau message" />
@endsection
