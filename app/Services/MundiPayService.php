<?php

namespace App\Services;

use App\DTO\CreateTransactionDto;
use App\DTO\PaymentResult;
use App\Enums\TransactionStatus;
use App\Services\Payments\PaymentResponseModel;
use App\Services\Payments\PaymentStrategy;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MundiPayService implements PaymentStrategy
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $apiSecret;

    protected string $transactionPath;

    public function __construct()
    {
        $this->apiKey = (string) config('payments.mundi.key');
        $this->apiSecret = (string) config('payments.mundi.secret');
        $configuredUrl = rtrim((string) config('payments.mundi.url'), '/');
        $this->baseUrl = (string) preg_replace('#/smobilpay$#i', '', $configuredUrl);
        $this->transactionPath = trim((string) config('payments.mundi.transaction_path', '/transaction'), '/');

        if (blank($this->apiKey) || blank($this->apiSecret) || blank($this->baseUrl) || blank($this->transactionPath)) {
            throw new RuntimeException('Configuration MundiPay incomplète.');
        }
    }

    protected function getHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-Key' => $this->apiKey,
            'X-API-Secret' => $this->apiSecret,
        ];
    }

    public function startPayment(CreateTransactionDto $dto): PaymentResponseModel
    {
        $subscriptionId = $dto->providerServiceReference();

        if (blank($subscriptionId)) {
            throw new RuntimeException('MundiPay exige l’identifiant de souscription du service de paiement.');
        }

        $body = [
            'amount' => $dto->amount,
            'subscription_id' => (int) $subscriptionId,
            'country_code' => $this->countryCode($dto->countryCode),
            'phone_number' => '+' . PhoneNumber::msisdn($dto->phoneNumber),
        ];

        Log::info('Initialisation d’un paiement MundiPay.', [
            'local_reference' => $dto->reference,
            'subscription_id' => $body['subscription_id'],
            'amount' => $body['amount'],
        ]);

        $response = Http::withHeaders($this->getHeaders())
            ->timeout((int) config('payments.mundi.timeout', 30))
            ->post($this->endpoint($this->transactionPath), $body);

        Log::info('MundiPay payment response', [
            'status' => $response->status(),
            'msg_code' => $response->json('msg_code'),
            'success' => $response->json('success'),
        ]);

        if ($response->failed() || (int) $response->json('success', 0) !== 1) {
            throw new RuntimeException($this->errorMessage($response->json(), 'MundiPay a refusé l’initialisation du paiement.'));
        }

        $result = $response->json('result.transaction');

        if (! is_array($result) || blank($result['id'] ?? null) || blank($result['pay_token'] ?? null)) {
            throw new RuntimeException('Réponse MundiPay invalide : id ou pay_token absent.');
        }

        return new PaymentResponseModel([
            'status' => strtoupper((string) ($result['status'] ?? 'PENDING')),
            'reference_id' => $dto->reference,
            'order_id' => $dto->reference,
            'provider_reference' => (string) $result['id'],
            'payment_token' => (string) $result['pay_token'],
            'phone_number' => $dto->phoneNumber,
            'amount_total' => $result['amount'] ?? $dto->amount,
            'provider_payload' => $result,
        ]);
    }

    public function checkStatus(string $paymentToken): PaymentResponseModel
    {
        $paymentToken = trim($paymentToken);

        if (blank($paymentToken)) {
            throw new RuntimeException('MundiPay status check failed: pay_token vide.');
        }

        $response = Http::withHeaders($this->getHeaders())
            ->timeout((int) config('payments.mundi.timeout', 30))
            ->retry(2, 250, throw: false)
            ->get($this->endpoint($this->transactionPath . '/' . rawurlencode($paymentToken)));

        if ($response->failed() || (int) $response->json('success', 0) !== 1) {
            Log::error('MundiPay status check error', [
                'status' => $response->status(),
                'pay_token' => $paymentToken,
                'msg_code' => $response->json('msg_code'),
            ]);

            throw new RuntimeException($this->errorMessage($response->json(), 'La vérification MundiPay a échoué.'));
        }

        $result = $response->json('result') ?? [];

        if (! is_array($result)) {
            throw new RuntimeException('Réponse MundiPay invalide : résultat de transaction absent.');
        }

        return new PaymentResponseModel([
            'status' => strtoupper((string) ($result['status'] ?? 'PENDING')),
            'reference_id' => $paymentToken,
            'provider_reference' => isset($result['id']) ? (string) $result['id'] : null,
            'payment_token' => (string) ($result['pay_token'] ?? $paymentToken),
            'amount_total' => $result['amount'] ?? null,
            'transaction_type' => $result['transaction_type'] ?? null,
            'provider_payload' => $result,
        ]);
    }

    public function processPayment(CreateTransactionDto $dto): PaymentResult
    {
        try {
            $paymentResponse = $this->startPayment($dto);
            $status = strtoupper((string) ($paymentResponse->data['status'] ?? ''));

            $transactionStatus = match (true) {
                in_array($status, ['PENDING', 'PROCESSING', 'SUCCESS', 'SUCCESSFUL', 'PAID'], true) => TransactionStatus::PENDING,
                default => TransactionStatus::FAILED,
            };

            return new PaymentResult(
                status: $transactionStatus,
                providerReference: $paymentResponse->data['provider_reference'] ?? null,
                externalReference: $paymentResponse->data['reference_id'] ?? null,
                message: $transactionStatus === TransactionStatus::PENDING
                    ? 'Payment processed successfully with MundiPay'
                    : null,
                paymentResponseModel: $paymentResponse,
                amount: $paymentResponse->data['amount_total'] ?? null,
                currency: $dto->currency,
                paymentIntent: $paymentResponse->data['payment_token'] ?? null,
            );
        } catch (Throwable $e) {
            Log::error('MundiPay processPayment error', ['error' => $e->getMessage()]);

            return new PaymentResult(
                status: TransactionStatus::FAILED,
                error: "MundiPay payment failed: {$e->getMessage()}",
            );
        }
    }

    public function cancelPayment(string $providerReference): PaymentResult
    {
        return new PaymentResult(
            status: TransactionStatus::CANCELLED,
            providerReference: $providerReference,
            message: 'Payment cancelled with MundiPay',
        );
    }

    public function verifyPayment(string $providerReference, ?string $paymentToken = null): PaymentResult
    {
        try {
            $paymentResponse = $this->checkStatus($paymentToken ?: $providerReference);
            $status = strtoupper((string) ($paymentResponse->data['status'] ?? ''));

            $transactionStatus = match (true) {
                in_array($status, ['SUCCESS', 'SUCCESSFUL', 'PAID'], true) => TransactionStatus::SUCCESS,
                in_array($status, ['PENDING', 'PROCESSING'], true) => TransactionStatus::PENDING,
                default => TransactionStatus::FAILED,
            };

            return new PaymentResult(
                status: $transactionStatus,
                providerReference: $paymentResponse->data['provider_reference'] ?? $providerReference,
                paymentResponseModel: $paymentResponse,
                amount: $paymentResponse->data['amount_total'] ?? null,
                paymentIntent: $paymentResponse->data['payment_token'] ?? $paymentToken,
            );
        } catch (Throwable $e) {
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

    private function endpoint(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    private function countryCode(?string $countryCode): string
    {
        $countryCode = strtoupper(trim((string) $countryCode));

        return in_array($countryCode, ['', '237', '+237', 'CM'], true) ? 'CMR' : $countryCode;
    }

    private function errorMessage(mixed $payload, string $fallback): string
    {
        if (! is_array($payload)) {
            return $fallback;
        }

        $message = data_get($payload, 'message')
            ?? data_get($payload, 'error')
            ?? data_get($payload, 'result.message');

        return is_scalar($message) && filled((string) $message) ? (string) $message : $fallback;
    }
}
