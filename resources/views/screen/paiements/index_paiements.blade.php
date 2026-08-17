@extends('nav')

@section('title', 'Paiements')

@section('content')
    @php
        $columns = [
            ['key' => 'name', 'label' => 'Client', 'type' => 'identity', 'secondaryKey' => 'phone'],
            ['key' => 'amount', 'label' => 'Montant', 'type' => 'currency', 'emphasis' => true],
            ['key' => 'category', 'label' => 'Catégorie'],
            ['key' => 'quantity', 'label' => 'Codes', 'type' => 'number'],
            ['key' => 'status', 'label' => 'Statut', 'type' => 'status'],
            ['key' => 'createdAt', 'label' => 'Demandé le'],
        ];
        $items = $paiements->map(function ($payment) {
            $name = trim(($payment->user?->name ?? '').' '.($payment->user?->last_name ?? '')) ?: 'Utilisateur inconnu';
            $status = $payment->paiement_date ? 'Validé' : 'En attente';
            return [
                'id' => $payment->id,
                'name' => $name,
                'phone' => $payment->numero_payeur ?: $payment->user?->phone,
                'amount' => (float) $payment->montant,
                'category' => $payment->categorie?->libelle,
                'quantity' => (int) $payment->nombre_de_code,
                'status' => $status,
                'createdAt' => $payment->created_at?->format('d/m/Y H:i'),
                'actionUrl' => route('paiement.active', $payment),
                'actionLabel' => $payment->paiement_date ? 'Consulter' : 'Examiner le paiement',
                'highlight' => ['label' => 'Montant demandé', 'value' => (float) $payment->montant, 'type' => 'currency', 'helper' => 'Paiement #'.$payment->id],
                'details' => [
                    ['title' => 'Paiement', 'fields' => [
                        ['label' => 'Montant', 'value' => (float) $payment->montant, 'type' => 'currency'],
                        ['label' => 'Statut', 'value' => $status, 'type' => 'status'],
                        ['label' => 'Nombre de codes', 'value' => (int) $payment->nombre_de_code, 'type' => 'number'],
                        ['label' => 'Catégorie', 'value' => $payment->categorie?->libelle],
                        ['label' => 'Transaction', 'value' => $payment->transaction_id],
                    ]],
                    ['title' => 'Client', 'fields' => [
                        ['label' => 'Nom complet', 'value' => $name],
                        ['label' => 'Email', 'value' => $payment->user?->email, 'type' => 'email'],
                        ['label' => 'Numéro débité', 'value' => $payment->numero_payeur, 'type' => 'phone'],
                        ['label' => 'Numéro à notifier', 'value' => $payment->numero_client, 'type' => 'phone'],
                    ]],
                    ['title' => 'Chronologie', 'fields' => [
                        ['label' => 'Demande créée', 'value' => $payment->created_at?->format('d/m/Y à H:i:s')],
                        ['label' => 'Validation', 'value' => $payment->paiement_date ? \Carbon\Carbon::parse($payment->paiement_date)->format('d/m/Y à H:i:s') : 'Non validé'],
                    ]],
                ],
            ];
        })->values();
    @endphp
    <x-admin.data-table eyebrow="Monétisation" title="Paiements" description="Suivez les demandes, les validations et les bénéficiaires des achats." :columns="$columns" :items="$items" :paginator="$paiements" search-placeholder="Client, téléphone, transaction ou catégorie…" />
@endsection
