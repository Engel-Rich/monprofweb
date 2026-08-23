<?php

namespace Tests\Unit;

use App\Services\Payments\CampayWebhookSignature;
use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PaymentWebhookSecurityTest extends TestCase
{
    private function jwt(string $key, array $payload = ['status' => 'SUCCESS']): string
    {
        $encode = fn (array $data): string => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');

        $header = $encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $body = $encode($payload);
        $signature = rtrim(strtr(base64_encode(
            hash_hmac('sha256', "{$header}.{$body}", $key, true)
        ), '+/', '-_'), '=');

        return "{$header}.{$body}.{$signature}";
    }

    public function test_a_signature_produced_with_the_webhook_key_is_accepted(): void
    {
        $this->assertTrue(CampayWebhookSignature::isValid($this->jwt('secret-key'), 'secret-key'));
    }

    public function test_a_signature_produced_with_another_key_is_rejected(): void
    {
        $this->assertFalse(CampayWebhookSignature::isValid($this->jwt('attacker-key'), 'secret-key'));
    }

    public function test_a_malformed_or_missing_signature_is_rejected(): void
    {
        $this->assertFalse(CampayWebhookSignature::isValid(null, 'secret-key'));
        $this->assertFalse(CampayWebhookSignature::isValid('', 'secret-key'));
        $this->assertFalse(CampayWebhookSignature::isValid('not-a-jwt', 'secret-key'));
        $this->assertFalse(CampayWebhookSignature::isValid($this->jwt('secret-key'), ''));
    }

    /**
     * L'algorithme « none » est le contournement classique des vérifications JWT.
     */
    public function test_an_unsigned_token_is_rejected(): void
    {
        $encode = fn (array $data): string => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
        $token = $encode(['alg' => 'none', 'typ' => 'JWT']).'.'.$encode(['status' => 'SUCCESS']).'.';

        $this->assertFalse(CampayWebhookSignature::isValid($token, 'secret-key'));
    }

    public function test_phone_numbers_are_normalised_to_a_single_format(): void
    {
        foreach (['690000000', '+237690000000', '00237690000000', '237 690 00 00 00', '690-00-00-00'] as $input) {
            $this->assertSame('237690000000', PhoneNumber::msisdn($input), "Entrée : {$input}");
            $this->assertSame('690000000', PhoneNumber::local($input), "Entrée : {$input}");
            $this->assertTrue(PhoneNumber::isValidCameroonMobile($input), "Entrée : {$input}");
        }

        $this->assertFalse(PhoneNumber::isValidCameroonMobile('123'));
        $this->assertFalse(PhoneNumber::isValidCameroonMobile(null));
        $this->assertFalse(PhoneNumber::isValidCameroonMobile('222000000'));
    }
}
