<?php

namespace App\DTO;

use App\Enums\TransactionSense;
use App\Enums\TransactionType;

class CreateTransactionDto
{
    public function __construct(
        public readonly TransactionType $type,
        public readonly ?string $userId = null,
        public readonly ?string $countryServiceProvidedId = null,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $reference = null,
        public readonly ?string $description = null,
        public readonly ?string $phoneNumber = null,
        public readonly ?string $countryId = null,
        public readonly ?string $countryCode = null,
        public readonly ?string $phoneCountryCode = null,
        public readonly ?string $providerServiceId = null,
        public readonly ?string $transactionPaymentId = null,
        public readonly ?TransactionSense $sense = null,
        public readonly ?array $metadata = null,
        public readonly ?array $paymentMethod = null,
    ) {}

    /**
     * Construit le DTO depuis un tableau (ex: $request->validated())
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: TransactionType::from($data['type']),
            userId: $data['userId'] ?? null,
            countryServiceProvidedId: $data['countryServiceProvidedId'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            currency: $data['currency'] ?? null,
            reference: $data['reference'] ?? null,
            description: $data['description'] ?? null,
            phoneNumber: $data['phoneNumber'] ?? null,
            countryId: $data['countryId'] ?? null,
            countryCode: $data['countryCode'] ?? null,
            phoneCountryCode: $data['phoneCountryCode'] ?? null,
            providerServiceId: $data['providerServiceId'] ?? $data['transactionPaymentId'] ?? null,
            transactionPaymentId: $data['transactionPaymentId'] ?? null,
            sense: isset($data['sense'])
                                          ? TransactionSense::from($data['sense'])
                                          : null,
            metadata: $data['metadata'] ?? null,
            paymentMethod: $data['paymentMethod'] ?? null,
        );
    }

    /**
     * Détermine si la transaction est un dépôt (entrée d'argent)
     */
    public function isDeposit(): bool
    {
        return $this->type === TransactionType::DEPOSIT
            || $this->sense === TransactionSense::IN;
    }

    public function providerServiceReference(): ?string
    {
        return $this->providerServiceId ?? $this->transactionPaymentId;
    }
}
