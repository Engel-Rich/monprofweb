<?php

namespace Tests\Unit;

use App\Models\PayementServices;
use Tests\TestCase;

class PaymentServiceCompatibilityTest extends TestCase
{
    public function test_legacy_fields_are_preserved_in_serialized_services(): void
    {
        $service = new PayementServices([
            'payment_provider_id' => 1,
            'title' => 'MTN MOBILE MONEY',
            'img' => null,
            'description' => 'Dépôt',
            'status' => 1,
            'subtitle' => 'Deposit',
            'is_active' => true,
            'reg_exp' => null,
            'subscription_id' => 2,
            'sens' => 'IN',
        ]);

        $serialized = $service->toArray();

        $this->assertArrayHasKey('title', $serialized);
        $this->assertArrayHasKey('img', $serialized);
        $this->assertArrayHasKey('description', $serialized);
        $this->assertArrayHasKey('status', $serialized);
        $this->assertArrayHasKey('subtitle', $serialized);
        $this->assertArrayHasKey('is_active', $serialized);
        $this->assertArrayHasKey('reg_exp', $serialized);
        $this->assertArrayHasKey('subscription_id', $serialized);
        $this->assertArrayHasKey('sens', $serialized);
        $this->assertArrayHasKey('payment_provider_id', $serialized);
        $this->assertArrayHasKey('image_url', $serialized);
    }
}
