@extends('nav')

@section('title', 'Codes')

@section('content')
    @php
        $columns = [
            ['key' => 'code', 'label' => 'Code', 'emphasis' => true],
            ['key' => 'student', 'label' => 'Bénéficiaire', 'type' => 'identity', 'secondaryKey' => 'phone'],
            ['key' => 'status', 'label' => 'Statut', 'type' => 'status'],
            ['key' => 'createdAt', 'label' => 'Généré le'],
            ['key' => 'activatedAt', 'label' => 'Activé le'],
        ];
        $items = $codes->map(function ($item) {
            $student = trim(($item->eleve?->user?->name ?? '').' '.($item->eleve?->user?->last_name ?? '')) ?: 'Non attribué';
            $status = $item->actif ? 'Activé' : 'En attente';
            return [
                'id' => $item->id,
                'code' => $item->code,
                'drawerTitle' => 'Code '.$item->code,
                'student' => $student,
                'phone' => $item->paiement?->numero_client,
                'status' => $status,
                'createdAt' => $item->created_at?->format('d/m/Y H:i'),
                'activatedAt' => $item->active_date ? \Carbon\Carbon::parse($item->active_date)->format('d/m/Y H:i') : null,
                'highlight' => ['label' => 'Code d’accès', 'value' => $item->code, 'helper' => 'Identifiant interne #'.$item->id],
                'details' => [
                    ['title' => 'Activation', 'fields' => [
                        ['label' => 'Statut', 'value' => $status, 'type' => 'status'],
                        ['label' => 'Généré le', 'value' => $item->created_at?->format('d/m/Y à H:i:s')],
                        ['label' => 'Activé le', 'value' => $item->active_date ? \Carbon\Carbon::parse($item->active_date)->format('d/m/Y à H:i:s') : 'Non activé'],
                    ]],
                    ['title' => 'Attribution', 'fields' => [
                        ['label' => 'Élève', 'value' => $student],
                        ['label' => 'Numéro SMS', 'value' => $item->paiement?->numero_client, 'type' => 'phone'],
                        ['label' => 'Paiement', 'value' => $item->paiements_id ? '#'.$item->paiements_id : null],
                    ]],
                ],
            ];
        })->values();
    @endphp
    <x-admin.data-table eyebrow="Accès" title="Codes d’activation" description="Contrôlez la génération, l’attribution et l’activation des codes élèves." :columns="$columns" :items="$items" :paginator="$codes" search-placeholder="Code, élève ou numéro SMS…" />
@endsection
