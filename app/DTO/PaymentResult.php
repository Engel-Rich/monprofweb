<?php

namespace App\DTO;

use App\Enums\TransactionStatus;
use App\Services\Payments\PaymentResponseModel;

class PaymentResult
{
    public readonly ?string $providerReference;

    /**
     * Alias temporaire conservé pour les consommateurs historiques.
     * Utiliser providerReference dans tout nouveau code.
     */
    public readonly ?string $transactionId;

    public function __construct(
        public readonly ?TransactionStatus $status = null,
        ?string $providerReference = null,
        ?string $transactionId = null,
        public readonly ?string $externalReference = null,
        public readonly ?string $message = null,
        public readonly ?string $error = null,
        public readonly ?PaymentResponseModel $paymentResponseModel = null,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $paymentIntent = null,
    ) {
        $this->providerReference = $providerReference ?? $transactionId;
        $this->transactionId = $this->providerReference;
    }

    public function isSuccess(): bool
    {
        return $this->status === TransactionStatus::SUCCESS;
    }

    public function isPending(): bool
    {
        return $this->status === TransactionStatus::PENDING
            || $this->status === TransactionStatus::PROCESSING;
    }

    public function isFailed(): bool
    {
        return in_array($this->status, [
            TransactionStatus::FAILED,
            TransactionStatus::ERROR,
            TransactionStatus::CANCELLED,
        ]);
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status?->value,
            'provider_reference' => $this->providerReference,
            // Compatibilité API : ne pas retirer avant migration des clients.
            'transaction_id' => $this->providerReference,
            'external_reference' => $this->externalReference,
            'message' => $this->message,
            'error' => $this->error,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_intent' => $this->paymentIntent,
        ];
    }
}
