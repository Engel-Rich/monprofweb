<?php

namespace Tests\Unit;

use App\DTO\CreateTransactionDto;
use App\DTO\PaymentResult;
use App\Models\PaymentProvider;
use App\Services\Payments\PaymentFactory;
use App\Services\Payments\PaymentResponseModel;
use App\Services\Payments\PaymentStrategy;
use Tests\TestCase;

class PaymentFactoryTest extends TestCase
{
    public function test_it_resolves_the_strategy_from_a_provider_model(): void
    {
        PaymentFactory::extend('TEST_PROVIDER', TestPaymentStrategy::class);

        $strategy = PaymentFactory::make(new PaymentProvider([
            'name' => 'Test provider',
            'code' => 'test_provider',
        ]));

        $this->assertInstanceOf(TestPaymentStrategy::class, $strategy);
        $this->assertSame('TEST_PROVIDER', $strategy->getProviderName());
    }
}

class TestPaymentStrategy implements PaymentStrategy
{
    public function startPayment(CreateTransactionDto $dto): PaymentResponseModel
    {
        throw new \LogicException('Not used by this unit test.');
    }

    public function checkStatus(string $reference): PaymentResponseModel
    {
        throw new \LogicException('Not used by this unit test.');
    }

    public function processPayment(CreateTransactionDto $dto): PaymentResult
    {
        throw new \LogicException('Not used by this unit test.');
    }

    public function cancelPayment(string $transactionId): PaymentResult
    {
        throw new \LogicException('Not used by this unit test.');
    }

    public function verifyPayment(string $transactionId): PaymentResult
    {
        throw new \LogicException('Not used by this unit test.');
    }

    public function getProviderName(): string
    {
        return 'TEST_PROVIDER';
    }
}
