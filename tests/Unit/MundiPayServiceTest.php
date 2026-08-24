<?php

namespace Tests\Unit;

use App\DTO\CreateTransactionDto;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Services\MundiPayService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MundiPayServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'payments.mundi.url' => 'https://mundipay.test/api/smobilpay/',
            'payments.mundi.key' => 'public-key',
            'payments.mundi.secret' => 'secret-key',
            'payments.mundi.timeout' => 5,
        ]);
    }

    public function test_it_creates_a_transaction_with_the_documented_contract(): void
    {
        Http::fake([
            'https://mundipay.test/api/v1/transaction' => Http::response([
                'msg_code' => 'MP001',
                'success' => 1,
                'result' => [
                    'transaction' => [
                        'id' => 987,
                        'pay_token' => 'TXN123456789',
                        'amount' => 1000,
                        'status' => 'pending',
                    ],
                ],
            ]),
        ]);

        $response = app(MundiPayService::class)->startPayment(new CreateTransactionDto(
            type: TransactionType::DEPOSIT,
            amount: 1000,
            reference: 'MPP-local-reference',
            phoneNumber: '690000000',
            countryCode: '237',
            providerServiceId: '42',
        ));

        $this->assertSame('987', $response->data['provider_reference']);
        $this->assertSame('987', $response->data['transaction_id']);
        $this->assertSame('TXN123456789', $response->data['payment_token']);
        $this->assertSame('PENDING', $response->data['status']);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://mundipay.test/api/v1/transaction'
            && $request->hasHeader('X-API-Key', 'public-key')
            && $request->hasHeader('X-API-Secret', 'secret-key')
            && $request['subscription_id'] === 42
            && $request['country_code'] === 'CMR'
            && $request['phone_number'] === '+237690000000');
    }

    public function test_it_checks_the_status_with_the_pay_token_and_keeps_the_provider_reference(): void
    {
        Http::fake([
            'https://mundipay.test/api/v1/transaction/TXN123456789' => Http::response([
                'msg_code' => 'MP001',
                'success' => 1,
                'result' => [
                    'id' => 987,
                    'pay_token' => 'TXN123456789',
                    'amount' => 1000,
                    'status' => 'paid',
                    'transaction_type' => 'deposit',
                ],
            ]),
        ]);

        $result = app(MundiPayService::class)->verifyPayment('987', 'TXN123456789');

        $this->assertSame(TransactionStatus::SUCCESS, $result->status);
        $this->assertSame('987', $result->providerReference);
        $this->assertSame('TXN123456789', $result->paymentIntent);
        $this->assertSame('987', $result->toArray()['transaction_id']);
        $this->assertSame('987', $result->toArray()['provider_reference']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://mundipay.test/api/v1/transaction/TXN123456789');
    }
}
