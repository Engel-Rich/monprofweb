<?php

namespace App\Services\Payments;

use App\DTO\CreateTransactionDto;
use App\DTO\PaymentResult;

interface PaymentStrategy
{
    public function startPayment(CreateTransactionDto $dto): PaymentResponseModel;
    public function checkStatus(string $reference): PaymentResponseModel;
    public function processPayment(CreateTransactionDto $dto): PaymentResult;
    public function cancelPayment(string $transactionId): PaymentResult;
    public function verifyPayment(string $transactionId): PaymentResult;
    public function getProviderName(): string;
}