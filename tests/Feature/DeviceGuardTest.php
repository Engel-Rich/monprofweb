<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Models\User;
use App\Support\DeviceGuard;
use Tests\TestCase;

class DeviceGuardTest extends TestCase
{
    private function user(array $attributes = []): User
    {
        return (new User)->forceFill(array_merge([
            'email' => 'eleve@example.test',
            'phone' => '690123456',
            'rule_id' => 2,
            'user_phone_emei' => 'device-A',
        ], $attributes));
    }

    public function test_the_registered_device_is_authorised(): void
    {
        $this->assertTrue(DeviceGuard::isAuthorized($this->user(), 'device-A'));
    }

    public function test_another_device_is_rejected(): void
    {
        $this->assertFalse(DeviceGuard::isAuthorized($this->user(), 'device-B'));
    }

    public function test_a_missing_device_identifier_is_rejected(): void
    {
        $this->assertFalse(DeviceGuard::isAuthorized($this->user(), null));
        $this->assertFalse(DeviceGuard::isAuthorized($this->user(), ''));
    }

    /**
     * Un compte sans appareil enregistré reste bloqué : il doit passer par la
     * réinitialisation par OTP, qui est le seul chemin qui écrit ce champ.
     */
    public function test_an_account_without_a_registered_device_is_rejected(): void
    {
        $this->assertFalse(DeviceGuard::isAuthorized($this->user(['user_phone_emei' => null]), 'device-A'));
    }

    public function test_parents_are_not_tied_to_a_device(): void
    {
        $this->assertTrue(DeviceGuard::isAuthorized($this->user(['rule_id' => 3]), 'device-B'));
    }

    public function test_the_bypass_account_is_exempt(): void
    {
        config(['devices.bypass_emails' => ['engel@rich.com']]);

        $this->assertTrue(DeviceGuard::bypasses('ENGEL@rich.com'));
        $this->assertFalse(DeviceGuard::bypasses('autre@example.test'));
        $this->assertFalse(DeviceGuard::bypasses(null));
        $this->assertTrue(DeviceGuard::isAuthorized($this->user(['email' => 'engel@rich.com']), 'device-B'));
    }

    public function test_the_phone_hint_only_exposes_the_last_four_digits(): void
    {
        $this->assertSame('*****3456', DeviceGuard::maskPhone('690123456'));
        $this->assertSame('*****3456', DeviceGuard::maskPhone('+237 690 12 34 56'));
        $this->assertNull(DeviceGuard::maskPhone('12'));
        $this->assertNull(DeviceGuard::maskPhone(null));
    }

    public function test_the_error_payload_carries_a_machine_readable_code(): void
    {
        $payload = ApiErrorCode::DEVICE_NOT_AUTHORIZED->response(['phone_hint' => '*****3456']);

        $this->assertFalse($payload['status']);
        $this->assertSame('DEVICE_NOT_AUTHORIZED', $payload['code']);
        $this->assertSame($payload['error'], $payload['message']);
        $this->assertSame('*****3456', $payload['data']['phone_hint']);
    }
}
