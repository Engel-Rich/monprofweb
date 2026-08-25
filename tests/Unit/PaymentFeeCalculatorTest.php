<?php

namespace Tests\Unit;

use App\Models\PayementServices;
use App\Services\Payments\PaymentFeeCalculator;
use PHPUnit\Framework\TestCase;

class PaymentFeeCalculatorTest extends TestCase
{
    public function test_it_charges_the_configured_share_and_rounds_up(): void
    {
        $service = new PayementServices([
            'provider_fee_percentage' => 2.5,
            'user_fee_percentage' => 50,
        ]);

        $fees = (new PaymentFeeCalculator)->calculate(1500, $service);

        $this->assertSame(1500, $fees['base_amount']);
        $this->assertSame(38, $fees['provider_fee']);
        $this->assertSame(20, $fees['service_fee']);
        $this->assertSame(1520, $fees['total_amount']);
    }

    public function test_the_user_pays_all_fees_by_default(): void
    {
        $service = new PayementServices([
            'provider_fee_percentage' => 2.5,
            'user_fee_percentage' => 100,
        ]);

        $fees = (new PaymentFeeCalculator)->calculate(1001, $service);

        $this->assertSame(29, $fees['service_fee']);
        $this->assertSame(1030, $fees['total_amount']);
    }
}
