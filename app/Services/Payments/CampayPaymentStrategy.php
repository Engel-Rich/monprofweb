<?php

namespace App\Services\Payments;

use App\DTO\CreateTransactionDto;
use App\DTO\PaymentResult;
use App\Enums\TransactionStatus;
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

        $this->baseUrl  = config($isProd ? 'campay.prod_url'  : 'campay.url');
        $this->username = config($isProd ? 'campay.prod_username' : 'campay.username');
        $this->password = config($isProd ? 'campay.prod_password' : 'campay.password');
    }

    protected function getAuthHeader(): ?array
    {
        $response = Http::post("{$this->baseUrl}/token/", [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        if ($response->failed()) {
            Log::error('CamPay token error', ['body' => $response->json()]);
            return null;
        }

        Log::info('CamPay authentication response', $response->json());

        return [
            'Authorization' => 'Token ' . $response->json('token'),
            'Content-Type'  => 'application/json',
        ];
    }

    public function startPayment(CreateTransactionDto $dto): PaymentResponseModel
    {
        $isDeposit = $dto->isDeposit();
        $endpoint  = $isDeposit ? 'collect' : 'withdraw';

        $phone = $dto->phoneNumber;
        if(!str_starts_with($phone, "237")){
            $phone = "237$phone"; 
        }
        $body = [
            'amount'             => (string) $dto->amount,
            ($isDeposit ? 'from' : 'to') => $phone, //$dto->phoneNumber,
            'description'        => $dto->description ?? ($isDeposit ? 'Subscription Payment for Monprof' : 'Withdrawal From monprof'),
            'external_reference' => $dto->reference,
            "currency"=>"XAF",
        ];

        Log::info("CamPay payment body [{$endpoint}]", $body);

        $headers = $this->getAuthHeader();
        if (! $headers) {
            throw new \RuntimeException('CamPay authentication header not found');
        }

        $response = Http::withHeaders($headers)->post("{$this->baseUrl}/{$endpoint}/", $body);

        Log::info('CamPay payment response', [
            'status' => $response->status(),
            'body'   => $response->json(),
        ]);

        if ($response->failed()) {
            Log::error('CamPay payment error', ['body' => $response->json()]);
            throw new \RuntimeException('Response not found from CamPay');
        }

        if ($response->successful()) {
            return new PaymentResponseModel([
                'status'        => 'PENDING',
                'reference_id'  => $dto->reference,
                'order_id'      => $dto->reference,
                'transaction_id'=> $response->json('reference'),
                'phone_number'  => $dto->phoneNumber,
                'operator'      => $response->json('operator'),
                'amount_total'  => $response->json('amount_total'),
            ]);
        }

        return new PaymentResponseModel([
            'status'        => 'FAILED',
            'reference_id'  => $dto->reference,
            'order_id'      => $dto->reference,
            'transaction_id'=> $response->json('reference'),
            'phone_number'  => $dto->phoneNumber,
            'operator'      => $response->json('operator'),
            'amount_total'  => $response->json('amount_total'),
        ]);
    }

    public function checkStatus(string $reference): PaymentResponseModel
    {
        $headers = $this->getAuthHeader();
        if (! $headers) {
            throw new \RuntimeException('CamPay authentication header not found');
        }

        $response = Http::withHeaders($headers)
            ->get("{$this->baseUrl}/transaction/{$reference}/");

        if ($response->failed()) {
            throw new \RuntimeException("CamPay status check failed: {$response->status()}");
        }

        return new PaymentResponseModel([
            'status'        => $response->json('status'),
            'reference_id'  => $reference,
            'order_id'      => $response->json('reference'),
            'transaction_id'=> $response->json('reference'),
            'phone_number'  => $response->json('phone_number'),
            'operator'      => $response->json('operator'),
            'amount_total'  => $response->json('amount'),
            'currency'      => $response->json('currency'),
        ]);
    }

    public function processPayment(CreateTransactionDto $dto): PaymentResult
    {
        try {
            $paymentResponse = $this->startPayment($dto);
            $status = $paymentResponse->data['status'] ?? '';

            $transactionStatus = match (true) {
                in_array($status, ['SUCCESS', 'SUCCESSFUL']) => TransactionStatus::PENDING,
                $status === 'PENDING'                        => TransactionStatus::PENDING,
                default                                      => TransactionStatus::FAILED,
            };

            Log::info("Transactions Response", $paymentResponse);

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
            $status = $paymentResponse->data['status'] ?? '';

            $transactionStatus = match (true) {
                in_array($status, ['SUCCESS', 'SUCCESSFUL']) => TransactionStatus::SUCCESS,
                $status === 'PENDING'                        => TransactionStatus::PENDING,
                default                                      => TransactionStatus::FAILED,
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