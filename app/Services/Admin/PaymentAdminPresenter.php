<?php

namespace App\Services\Admin;

use App\Enums\TransactionStatus;
use App\Models\Paiements;

class PaymentAdminPresenter
{
    public const RELATIONS = [
        'user',
        'categorie',
        'transaction.provider',
        'transaction.paymentService.provider',
        'codes.eleve.user',
    ];

    public function status(Paiements $payment): string
    {
        if ($payment->status && $payment->paiement_date) {
            return 'Validé';
        }

        return match (strtoupper((string) $payment->transaction?->status)) {
            TransactionStatus::FAILED->value => 'Échoué',
            TransactionStatus::SUCCESS->value => 'À finaliser',
            TransactionStatus::PROCESSING->value => 'Traitement',
            default => 'En attente',
        };
    }

    public function item(Paiements $payment): array
    {
        $transaction = $payment->transaction;
        $service = $transaction?->paymentService;
        $provider = $transaction?->provider ?? $service?->provider;
        $name = trim(($payment->user?->name ?? '').' '.($payment->user?->last_name ?? '')) ?: 'Utilisateur inconnu';
        $status = $this->status($payment);
        $codes = $payment->codes->map(fn ($code) => sprintf(
            '%s · %s%s',
            $code->code,
            $code->actif ? 'utilisé' : 'disponible',
            $code->eleve?->user ? ' par '.trim($code->eleve->user->name.' '.$code->eleve->user->last_name) : '',
        ))->values()->all();

        return [
            'id' => $payment->id,
            'drawerTitle' => 'Paiement #'.$payment->id,
            'name' => $name,
            'phone' => $payment->numero_payeur ?: $payment->user?->phone,
            'amount' => (float) $payment->montant,
            'category' => $payment->categorie?->libelle,
            'quantity' => (int) $payment->nombre_de_code,
            'status' => $status,
            'searchText' => implode(' ', array_filter([
                $payment->id,
                $name,
                $payment->user?->email,
                $payment->numero_payeur,
                $payment->numero_client,
                $payment->categorie?->libelle,
                $transaction?->id,
                $transaction?->reference,
                $transaction?->provider_reference,
                $transaction?->payment_token,
                $transaction?->phone_number,
                $provider?->name,
                $provider?->code,
                $service?->title,
                ...$payment->codes->pluck('code')->all(),
            ])),
            'createdAt' => $payment->created_at?->format('d/m/Y H:i'),
            'imageUrl' => $provider?->image_url ?? $service?->image_url,
            'imageAlt' => $provider
                ? 'Logo '.$provider->name
                : ($service ? 'Logo '.$service->title : null),
            'actionUrl' => route('paiement.active', $payment),
            'actionLabel' => 'Ouvrir la fiche complète',
            'highlight' => [
                'label' => 'Montant demandé',
                'value' => (float) $payment->montant,
                'type' => 'currency',
                'helper' => $transaction?->reference ?: 'Paiement #'.$payment->id,
            ],
            'details' => [
                ['title' => 'Paiement', 'note' => 'Données MonProf', 'fields' => [
                    ['label' => 'ID paiement', 'value' => '#'.$payment->id, 'type' => 'code'],
                    ['label' => 'Montant', 'value' => (float) $payment->montant, 'type' => 'currency'],
                    ['label' => 'Statut', 'value' => $status, 'type' => 'status'],
                    ['label' => 'Catégorie', 'value' => $payment->categorie?->libelle],
                    ['label' => 'Nombre de codes', 'value' => (int) $payment->nombre_de_code, 'type' => 'number'],
                    ['label' => 'Codes générés', 'value' => $codes, 'type' => 'tags'],
                    ['label' => 'Date de validation', 'value' => $payment->paiement_date?->format('d/m/Y à H:i:s') ?: 'Non validé'],
                ]],
                ['title' => 'Transaction associée', 'note' => $transaction ? 'Transaction #'.$transaction->id : 'Introuvable', 'fields' => [
                    ['label' => 'ID local', 'value' => $transaction?->id ? '#'.$transaction->id : null, 'type' => 'code'],
                    ['label' => 'Référence MonProf', 'value' => $transaction?->reference, 'type' => 'code'],
                    ['label' => 'Référence fournisseur', 'value' => $transaction?->provider_reference, 'type' => 'code'],
                    ['label' => 'Pay token', 'value' => $transaction?->payment_token, 'type' => 'code'],
                    ['label' => 'Statut fournisseur', 'value' => $transaction?->status, 'type' => 'status'],
                    ['label' => 'Sens', 'value' => $transaction?->sens],
                    ['label' => 'Montant demandé au fournisseur', 'value' => $transaction?->amount, 'type' => 'currency'],
                    ['label' => 'Téléphone transaction', 'value' => $transaction?->phone_number, 'type' => 'phone'],
                    ['label' => 'Motif du rejet', 'value' => $transaction?->raison_reject],
                    ['label' => 'Service interne', 'value' => $transaction?->internal_service, 'type' => 'code'],
                ]],
                ['title' => 'Fournisseur et service', 'fields' => [
                    ['label' => 'Fournisseur', 'value' => $provider ? "{$provider->name} ({$provider->code})" : null],
                    ['label' => 'ID fournisseur', 'value' => $provider?->id ? '#'.$provider->id : null, 'type' => 'code'],
                    ['label' => 'Service', 'value' => $service?->title],
                    ['label' => 'ID service', 'value' => $service?->id ? '#'.$service->id : null, 'type' => 'code'],
                    ['label' => 'Subscription ID', 'value' => $transaction?->subscription_id, 'type' => 'code'],
                ]],
                ['title' => 'Client et bénéficiaire', 'fields' => [
                    ['label' => 'Utilisateur', 'value' => $name],
                    ['label' => 'ID utilisateur', 'value' => $payment->user_id ? '#'.$payment->user_id : null, 'type' => 'code'],
                    ['label' => 'E-mail', 'value' => $payment->user?->email, 'type' => 'email'],
                    ['label' => 'Numéro débité', 'value' => $payment->numero_payeur, 'type' => 'phone'],
                    ['label' => 'Numéro à notifier', 'value' => $payment->numero_client, 'type' => 'phone'],
                ]],
                ['title' => 'Suivi technique', 'note' => '10 derniers contrôles', 'fields' => [
                    ['label' => 'Créée le', 'value' => $payment->created_at?->format('d/m/Y à H:i:s')],
                    ['label' => 'Mise à jour le', 'value' => $payment->updated_at?->format('d/m/Y à H:i:s')],
                    ['label' => 'Métadonnées', 'value' => $transaction?->metadatas, 'type' => 'json'],
                ]],
            ],
            'actions' => $this->actions($payment),
        ];
    }

    public function actions(Paiements $payment): array
    {
        $transaction = $payment->transaction;
        $status = strtoupper((string) $transaction?->status);
        $hasCodes = $payment->codes->isNotEmpty();
        $hasUsedCode = $payment->codes->contains(fn ($code) => $code->actif);
        $riskyActivation = ! $payment->status
            || in_array($status, [TransactionStatus::PENDING->value, TransactionStatus::PROCESSING->value, TransactionStatus::FAILED->value], true)
            || $hasUsedCode;

        $actions = [];

        if ($transaction) {
            $actions[] = [
                'label' => 'Revérifier',
                'url' => route('paiement.reverify', $payment),
                'method' => 'POST',
                'style' => 'secondary',
                'icon' => 'refresh',
                'disabled' => blank($transaction->provider_reference),
                'confirm' => [
                    'title' => $status === TransactionStatus::FAILED->value
                        ? 'Revérifier une transaction échouée ?'
                        : 'Interroger le fournisseur ?',
                    'description' => 'MonProf va demander le statut actuel au fournisseur et réconcilier le paiement local.',
                    'warning' => $status === TransactionStatus::FAILED->value
                        ? 'Un succès tardif peut valider le paiement et générer les codes.'
                        : null,
                    'confirmLabel' => 'Revérifier maintenant',
                ],
            ];
        }

        if ($hasCodes && $payment->status) {
            $actions[] = [
                'label' => $payment->codes->count() === 1 ? 'Renvoyer le SMS' : 'Renvoyer les codes',
                'url' => route('paiement.resend-notification', $payment),
                'method' => 'POST',
                'style' => 'secondary',
                'icon' => 'send',
                'confirm' => [
                    'title' => $hasUsedCode ? 'Renvoyer un code déjà utilisé ?' : 'Renvoyer le code au bénéficiaire ?',
                    'description' => $payment->codes->count() === 1
                        ? 'Le code sera renvoyé par SMS au numéro à notifier.'
                        : 'La liste des codes sera renvoyée par e-mail.',
                    'warning' => $hasUsedCode ? 'Au moins un code a déjà été activé par un élève.' : null,
                    'confirmLabel' => 'Confirmer l’envoi',
                ],
            ];
        }

        $actions[] = [
            'label' => 'Activer le code',
            'url' => route('paiement.activate', $payment),
            'method' => 'POST',
            'style' => $riskyActivation ? 'danger' : 'primary',
            'icon' => 'activate',
            'confirm' => [
                'title' => $payment->status ? 'Relancer l’activation du paiement ?' : 'Activer manuellement ce paiement ?',
                'description' => 'Cette action valide le paiement et génère les codes manquants.',
                'warning' => $this->activationWarning($status, $hasUsedCode),
                'confirmLabel' => $riskyActivation ? 'Forcer l’activation' : 'Activer le code',
                'tone' => $riskyActivation ? 'danger' : 'primary',
            ],
        ];

        return $actions;
    }

    private function activationWarning(string $transactionStatus, bool $hasUsedCode): ?string
    {
        $warnings = [];

        if (in_array($transactionStatus, [TransactionStatus::PENDING->value, TransactionStatus::PROCESSING->value], true)) {
            $warnings[] = 'La transaction est encore en attente chez le fournisseur.';
        } elseif ($transactionStatus === TransactionStatus::FAILED->value) {
            $warnings[] = 'La transaction est marquée comme échouée.';
        }

        if ($hasUsedCode) {
            $warnings[] = 'Au moins un code a déjà été utilisé.';
        }

        return $warnings === [] ? null : implode(' ', $warnings);
    }
}
