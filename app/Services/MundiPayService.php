<?php

namespace App\Services;

use App\DTO\CreateTransactionDto;
use App\DTO\PaymentResult;
use App\Enums\TransactionStatus;
use App\Services\Payments\PaymentResponseModel;
use App\Services\Payments\PaymentStrategy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MundiPayService implements PaymentStrategy
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $apiSecret;

    public function __construct()
    {
        $this->apiKey = (string) config('payments.mundi.key');
        $this->apiSecret = (string) config('payments.mundi.secret');
        $this->baseUrl = rtrim((string) config('payments.mundi.url'), '/').'/';

        if (blank($this->apiKey) || blank($this->apiSecret) || blank($this->baseUrl)) {
            throw new \RuntimeException('Configuration MundiPay incomplète.');
        }
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-API-KEY' => $this->apiKey,
            'X-API-SECRET' => $this->apiSecret,
        ];
    }

    public function startPayment(CreateTransactionDto $dto): PaymentResponseModel
    {
        $body = [
            'amount' => $dto->amount,
            'subscription_id' => $dto->transactionPaymentId,
            'country_code' => $dto->countryCode ?? '237',
            'phone_number' => $dto->phoneNumber,
        ];

        Log::info('MundiPay payment body', $body);

        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}payment/transaction", $body);

        Log::info('MundiPay payment response', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if ($response->failed()) {
            Log::error('MundiPay payment error', ['body' => $response->json()]);
            throw new \RuntimeException('MundiPay payment initiation failed');
        }

        $result = $response->json('result') ?? [];

        return new PaymentResponseModel([
            'status' => 'PENDING',
            'reference_id' => $dto->reference,
            'order_id' => $dto->reference,
            'transaction_id' => $result['id'] ?? null,
            'payment_token' => $result['pay_token'] ?? null,
            'phone_number' => $dto->phoneNumber,
            'amount_total' => $dto->amount,
        ]);
    }

    public function checkStatus(string $reference): PaymentResponseModel
    {
        $response = Http::withHeaders($this->getHeaders())
            ->get("{$this->baseUrl}payment/transaction/{$reference}");

        if ($response->failed()) {
            throw new \RuntimeException("MundiPay status check failed: {$response->status()}");
        }

        $result = $response->json('result') ?? [];
        $status = data_get($result, 'status') ?? $response->json('status') ?? 'PENDING';

        return new PaymentResponseModel([
            'status' => $status,
            'reference_id' => $reference,
            'transaction_id' => $result['id'] ?? null,
            'amount_total' => $result['amount'] ?? null,
        ]);
    }

    public function processPayment(CreateTransactionDto $dto): PaymentResult
    {
        try {
            $paymentResponse = $this->startPayment($dto);
            $status = strtoupper((string) ($paymentResponse->data['status'] ?? ''));

            $transactionStatus = match (true) {
                in_array($status, ['SUCCESS', 'SUCCESSFUL']) => TransactionStatus::PENDING,
                $status === 'PENDING' => TransactionStatus::PENDING,
                default => TransactionStatus::FAILED,
            };

            return new PaymentResult(
                status: $transactionStatus,
                transactionId: $paymentResponse->data['transaction_id'] ?? null,
                externalReference: $paymentResponse->data['reference_id'] ?? null,
                message: $transactionStatus === TransactionStatus::PENDING
                    ? 'Payment processed successfully with MundiPay'
                    : null,
                paymentResponseModel: $paymentResponse,
                amount: $paymentResponse->data['amount_total'] ?? null,
                currency: $dto->currency,
                paymentIntent: $paymentResponse->data['payment_token'] ?? null,
            );
        } catch (\Throwable $e) {
            Log::error('MundiPay processPayment error', ['error' => $e->getMessage()]);

            return new PaymentResult(
                status: TransactionStatus::FAILED,
                error: "MundiPay payment failed: {$e->getMessage()}",
            );
        }
    }

    public function cancelPayment(string $transactionId): PaymentResult
    {
        return new PaymentResult(
            status: TransactionStatus::CANCELLED,
            transactionId: $transactionId,
            message: 'Payment cancelled with MundiPay',
        );
    }

    public function verifyPayment(string $transactionId): PaymentResult
    {
        try {
            $paymentResponse = $this->checkStatus($transactionId);
            $status = strtoupper((string) ($paymentResponse->data['status'] ?? ''));

            $transactionStatus = match (true) {
                in_array($status, ['SUCCESS', 'SUCCESSFUL', 'PAID'], true) => TransactionStatus::SUCCESS,
                in_array($status, ['PENDING', 'PROCESSING'], true) => TransactionStatus::PENDING,
                default => TransactionStatus::FAILED,
            };

            return new PaymentResult(
                status: $transactionStatus,
                transactionId: $transactionId,
                paymentResponseModel: $paymentResponse,
                amount: $paymentResponse->data['amount_total'] ?? null,
            );
        } catch (\Throwable $e) {
            Log::error('MundiPay verifyPayment error', ['error' => $e->getMessage()]);

            return new PaymentResult(
                status: TransactionStatus::ERROR,
                error: "MundiPay verification failed: {$e->getMessage()}",
            );
        }
    }

    public function getProviderName(): string
    {
        return 'MUNDIPAY';
    }
}
