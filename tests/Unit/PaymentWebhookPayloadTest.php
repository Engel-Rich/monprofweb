<?php

namespace Tests\Unit;

use App\DTO\CampayWebhookPayload;
use App\DTO\MundiPayWebhookPayload;
use PHPUnit\Framework\TestCase;

class PaymentWebhookPayloadTest extends TestCase
{
    public function test_campay_payload_keeps_its_own_reference_contract(): void
    {
        $dto = CampayWebhookPayload::normalize([
            'reference' => 'campay-provider-reference',
            'external_reference' => 'local-uuid',
            'status' => 'SUCCESSFUL',
        ]);

        $this->assertSame('CAMPAY', $dto->providerCode);
        $this->assertSame('campay-provider-reference', $dto->providerReference);
        $this->assertSame('MPP-local-uuid', $dto->localReference);
        $this->assertNull($dto->paymentToken);
    }

    public function test_mundipay_payload_reads_its_nested_transaction_contract(): void
    {
        $dto = MundiPayWebhookPayload::normalize([
            'success' => 1,
            'result' => [
                'transaction' => [
                    'id' => 987,
                    'pay_token' => 'TXN-123',
                    'status' => 'paid',
                ],
            ],
        ]);

        $this->assertSame('MUNDIPAY', $dto->providerCode);
        $this->assertSame('987', $dto->providerReference);
        $this->assertSame('TXN-123', $dto->paymentToken);
        $this->assertSame('PAID', $dto->status);
        $this->assertNull($dto->localReference);
    }
}
