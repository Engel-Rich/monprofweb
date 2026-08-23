<?php

namespace App\Services\Payments;

use App\DTO\CreateTransactionDto;
use App\DTO\PaymentResult;
use App\Enums\TransactionStatus;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CampayPaymentStrategy implements PaymentStrategy
{
    protected string $baseUrl;

    protected string $username;

    protected string $password;

    public function __construct()
    {
        $isProd = app()->environment('production');

        $this->baseUrl = rtrim((string) config($isProd ? 'campay.prod_url' : 'campay.url'), '/');
        $this->username = (string) config($isProd ? 'campay.prod_username' : 'campay.username');
        $this->password = (string) config($isProd ? 'campay.prod_password' : 'campay.password');

        if (blank($this->baseUrl) || blank($this->username) || blank($this->password)) {
            throw new \RuntimeException(
                $isProd
                    ? 'Configuration Campay production incomplète (CAMPAY_PROD_URL, CAMPAY_PROD_USERNAME, CAMPAY_PROD_PASSWORD).'
                    : 'Configuration Campay sandbox incomplète (CAMPAY_URL, CAMPAY_USERNAME, CAMPAY_PASSWORD).'
            );
        }
    }

    /**
     * Le token CamPay est valable une quinzaine de minutes : on le met en cache
     * pour ne pas rappeler /token/ à chaque passage du poller (toutes les 5 s),
     * ce qui déclenchait le throttling du fournisseur.
     */
    protected function getToken(): ?string
    {
        $ttl = max(1, (int) config('campay.token_ttl', 10));
        $cacheKey = 'campay:token:'.md5($this->baseUrl.'|'.$this->username);

        $token = Cache::get($cacheKey);

        if (filled($token)) {
            return $token;
        }

        $response = Http::acceptJson()->post("{$this->baseUrl}/token/", [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        if ($response->failed()) {
            Log::error('CamPay token error', [
                'status' => $response->status(),
                'base_url' => $this->baseUrl,
                'body' => $response->json() ?? $response->body(),
            ]);

            return null;
        }

        $token = $response->json('token');

        if (blank($token)) {
            Log::error('CamPay token error : réponse sans token.', ['status' => $response->status()]);

            return null;
        }

        Cache::put($cacheKey, $token, now()->addMinutes($ttl));

        return $token;
    }

    protected function forgetToken(): void
    {
        Cache::forget('campay:token:'.md5($this->baseUrl.'|'.$this->username));
    }

    protected function getAuthHeader(): ?array
    {
        $token = $this->getToken();

        if (blank($token)) {
            return null;
        }

        return [
            'Authorization' => 'Token '.$token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Format attendu par CamPay : 237XXXXXXXXX.
     * Gère les saisies « +237690000000 », « 00237 690 00 00 00 », « 690-00-00-00 ».
     */
    public static function normalizePhoneNumber(?string $phone): string
    {
        return PhoneNumber::msisdn($phone);
    }

    public function startPayment(CreateTransactionDto $dto): PaymentResponseModel
    {
        $isDeposit = $dto->isDeposit();
        $endpoint = $isDeposit ? 'collect' : 'withdraw';

        $phone = static::normalizePhoneNumber($dto->phoneNumber);
        $body = [
            'amount' => (string) $dto->amount,
            ($isDeposit ? 'from' : 'to') => $phone, //$dto->phoneNumber,
            'description' => $dto->description ?? ($isDeposit ? 'Subscription Payment for Monprof' : 'Withdrawal From monprof'),
            'external_reference' => $dto->reference,
            'currency' => 'XAF',
        ];

        Log::info("CamPay payment body [{$endpoint}]", $body);

        $headers = $this->getAuthHeader();
        if (! $headers) {
            throw new \RuntimeException('CamPay authentication header not found');
        }

        $response = Http::withHeaders($headers)->post("{$this->baseUrl}/{$endpoint}/", $body);

        Log::info('CamPay payment response', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if ($response->failed()) {
            Log::error('CamPay payment error', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            throw new \RuntimeException(sprintf(
                'CamPay %s failed: %d %s',
                $endpoint,
                $response->status(),
                json_encode($response->json() ?? $response->body(), JSON_UNESCAPED_UNICODE),
            ));
        }

        if ($response->successful()) {
            return new PaymentResponseModel([
                'status' => 'PENDING',
                'reference_id' => $dto->reference,
                'order_id' => $dto->reference,
                'transaction_id' => $response->json('reference'),
                'phone_number' => $dto->phoneNumber,
                'operator' => $response->json('operator'),
                'amount_total' => $response->json('amount_total'),
            ]);
        }

        return new PaymentResponseModel([
            'status' => 'FAILED',
            'reference_id' => $dto->reference,
            'order_id' => $dto->reference,
            'transaction_id' => $response->json('reference'),
            'phone_number' => $dto->phoneNumber,
            'operator' => $response->json('operator'),
            'amount_total' => $response->json('amount_total'),
        ]);
    }

    public function checkStatus(string $reference): PaymentResponseModel
    {
        $reference = trim($reference);

        // Sans cette garde, l'URL devient «/api/transaction//», que CamPay
        // normalise vers l'endpoint de liste et refuse avec un 403 trompeur.
        if (blank($reference)) {
            throw new \RuntimeException('CamPay status check failed: référence de transaction vide.');
        }

        $headers = $this->getAuthHeader();
        if (! $headers) {
            throw new \RuntimeException('CamPay authentication header not found');
        }

        $response = Http::withHeaders($headers)
            ->get("{$this->baseUrl}/transaction/{$reference}/");

        // Un token mis en cache peut avoir été révoqué côté CamPay : on le jette
        // et on retente une fois avec un token frais avant de déclarer l'échec.
        if (in_array($response->status(), [401, 403], true)) {
            $this->forgetToken();
            $headers = $this->getAuthHeader();

            if ($headers) {
                $response = Http::withHeaders($headers)
                    ->get("{$this->baseUrl}/transaction/{$reference}/");
            }
        }

        if ($response->failed()) {
            Log::error('CamPay status check error', [
                'status' => $response->status(),
                'reference' => $reference,
                'base_url' => $this->baseUrl,
                'body' => $response->json() ?? $response->body(),
            ]);

            throw new \RuntimeException(sprintf(
                'CamPay status check failed: %d %s',
                $response->status(),
                json_encode($response->json() ?? $response->body(), JSON_UNESCAPED_UNICODE),
            ));
        }

        return new PaymentResponseModel([
            'status' => $response->json('status'),
            'reference_id' => $reference,
            'order_id' => $response->json('reference'),
            'transaction_id' => $response->json('reference'),
            'phone_number' => $response->json('phone_number'),
            'operator' => $response->json('operator'),
            'amount_total' => $response->json('amount'),
            'currency' => $response->json('currency'),
            // Motif d'échec CamPay, repris dans la notification envoyée au client.
            'reason' => $response->json('reason'),
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

            // Log::info(["Transactions Response" =>  $paymentResponse,]);

            return new PaymentResult(
                status: $transactionStatus,
                transactionId: $paymentResponse->data['transaction_id'] ?? null,
                externalReference: $paymentResponse->data['reference_id'] ?? null,
                message: $transactionStatus === TransactionStatus::PENDING
                    ? 'Payment processed successfully with CamPay'
                    : null,
                paymentResponseModel: $paymentResponse,
                amount: $paymentResponse->data['amount_total'] ?? null,
                currency: $dto->currency,
                paymentIntent: $paymentResponse->data['transaction_id'] ?? null,
            );
        } catch (\Throwable $e) {
            Log::error('CamPay processPayment error', ['error' => $e->getMessage()]);

            return new PaymentResult(
                status: TransactionStatus::FAILED,
                error: "CamPay payment failed: {$e->getMessage()}",
            );
        }
    }

    public function cancelPayment(string $transactionId): PaymentResult
    {
        Log::info('Cancelling CamPay payment', ['transactionId' => $transactionId]);

        return new PaymentResult(
            status: TransactionStatus::CANCELLED,
            transactionId: $transactionId,
            message: 'Payment cancelled with CamPay',
        );
    }

    public function verifyPayment(string $transactionId): PaymentResult
    {
        try {
            $paymentResponse = $this->checkStatus($transactionId);
            $status = strtoupper((string) ($paymentResponse->data['status'] ?? ''));

            $transactionStatus = match (true) {
                in_array($status, ['SUCCESS', 'SUCCESSFUL'], true) => TransactionStatus::SUCCESS,
                in_array($status, ['PENDING', 'PROCESSING'], true) => TransactionStatus::PENDING,
                default => TransactionStatus::FAILED,
            };

            return new PaymentResult(
                status: $transactionStatus,
                transactionId: $transactionId,
                paymentResponseModel: $paymentResponse,
                amount: $paymentResponse->data['amount_total'] ?? null,
                currency: $paymentResponse->data['currency'] ?? null,
                paymentIntent: $paymentResponse->data['transaction_id'] ?? null,
            );
        } catch (\Throwable $e) {
            Log::error('CamPay verifyPayment error', ['error' => $e->getMessage()]);

            return new PaymentResult(
                status: TransactionStatus::ERROR,
                error: "CamPay payment verification failed: {$e->getMessage()}",
            );
        }
    }

    public function getProviderName(): string
    {
        return 'CAMPAY';
    }
}
