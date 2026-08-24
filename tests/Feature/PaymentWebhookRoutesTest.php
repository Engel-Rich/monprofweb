<?php

namespace Tests\Feature;

use App\Jobs\ProcessWebhook;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaymentWebhookRoutesTest extends TestCase
{
    public function test_campay_has_a_dedicated_webhook_route(): void
    {
        Queue::fake();
        config(['campay.webhook_key' => null]);

        $this->postJson(route('api.transaction.webhook.campay'), [
            'reference' => 'campay-reference',
            'external_reference' => 'local-reference',
            'status' => 'SUCCESSFUL',
        ])->assertOk();

        Queue::assertPushed(
            ProcessWebhook::class,
            fn (ProcessWebhook $job): bool => $job->dto->providerCode === 'CAMPAY'
                && $job->dto->providerReference === 'campay-reference'
                && $job->dto->localReference === 'MPP-local-reference',
        );
    }

    public function test_mundipay_has_a_dedicated_webhook_route(): void
    {
        Queue::fake();

        $this->postJson(route('api.transaction.webhook.mundipay'), [
            'result' => [
                'transaction' => [
                    'id' => 42,
                    'pay_token' => 'MUNDI-TOKEN',
                    'status' => 'paid',
                ],
            ],
        ])->assertOk();

        Queue::assertPushed(
            ProcessWebhook::class,
            fn (ProcessWebhook $job): bool => $job->dto->providerCode === 'MUNDIPAY'
                && $job->dto->providerReference === '42'
                && $job->dto->paymentToken === 'MUNDI-TOKEN',
        );
    }
}
