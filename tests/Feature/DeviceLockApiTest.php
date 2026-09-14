<?php

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Models\OTP;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Comportement de bout en bout du verrouillage par appareil, tel que le mobile
 * le rencontre : en-tête « phone-emei » sur une route authentifiée.
 */
class DeviceLockApiTest extends TestCase
{
    private const ENDPOINT = '/api/auth/fcm_token';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'devices.bypass_emails' => ['test@monprof.test', 'recette@monprof.test'],
            'devices.locked_rule_id' => 2,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createSchema();
    }

    // ─── Contrôle de l'appareil ───────────────────────────────────────────

    public function test_the_registered_device_goes_through(): void
    {
        $this->student();

        $this->call('PUT', self::ENDPOINT, ['fcm_token' => 'fcm-1'], [], [], $this->headers('device-A'))
            ->assertOk()
            ->assertJsonPath('status', true);
    }

    public function test_another_device_is_refused_with_a_machine_readable_code(): void
    {
        $this->student();

        $this->call('PUT', self::ENDPOINT, ['fcm_token' => 'fcm-1'], [], [], $this->headers('device-B'))
            ->assertStatus(422)
            ->assertJsonPath('status', false)
            ->assertJsonPath('code', ApiErrorCode::DEVICE_NOT_AUTHORIZED->value)
            ->assertJsonPath('data.phone_hint', '*****3456');
    }

    public function test_a_missing_device_header_is_refused(): void
    {
        $this->student();

        $this->call('PUT', self::ENDPOINT, ['fcm_token' => 'fcm-1'])
            ->assertStatus(422)
            ->assertJsonPath('code', ApiErrorCode::DEVICE_ID_MISSING->value);
    }

    /**
     * Le cas des comptes de test : ils doivent pouvoir travailler depuis
     * n'importe quel téléphone, y compris sur les routes authentifiées où
     * l'adresse email n'apparaît nulle part dans la requête.
     */
    public function test_a_bypass_account_goes_through_from_any_device(): void
    {
        foreach (['test@monprof.test', 'recette@monprof.test'] as $index => $email) {
            $this->student(email: $email, phone: '69000000'.$index, device: 'device-A');

            $this->call('PUT', self::ENDPOINT, ['fcm_token' => 'fcm-1'], [], [], $this->headers('un-tout-autre-telephone'))
                ->assertOk()
                ->assertJsonPath('status', true);
        }
    }

    public function test_a_bypass_account_without_any_registered_device_goes_through(): void
    {
        $this->student(email: 'test@monprof.test', device: null);

        $this->call('PUT', self::ENDPOINT, ['fcm_token' => 'fcm-1'], [], [], $this->headers('device-B'))
            ->assertOk();
    }

    public function test_a_parent_goes_through_from_any_device(): void
    {
        $this->student(email: 'parent@monprof.test', ruleId: 3);

        $this->call('PUT', self::ENDPOINT, ['fcm_token' => 'fcm-1'], [], [], $this->headers('device-B'))
            ->assertOk();
    }

    // ─── Révocation des jetons ────────────────────────────────────────────

    public function test_resetting_the_device_revokes_every_active_token(): void
    {
        $user = $this->student(device: 'ancien-telephone');
        $user->createToken('MONPROF_WEB');
        $user->createToken('MONPROF_WEB');
        $user->forceFill(['refresh_token' => 'un-refresh-token'])->save();

        $this->assertSame(2, $user->tokens()->count());

        $this->postJson('/api/user/reset_password', [
            'phone' => $user->phone,
            'type' => 'phone_emei',
            'otp' => $this->otp($user->phone),
            'verification_id' => 'verif-1',
        ], $this->headers('nouveau-telephone'))->assertOk()->assertJsonPath('status', true);

        $user->refresh();

        $this->assertSame('nouveau-telephone', $user->user_phone_emei);
        $this->assertSame(0, $user->tokens()->count());
        $this->assertNull($user->refresh_token);
    }

    public function test_resetting_the_password_revokes_every_active_token(): void
    {
        $user = $this->student();
        $user->createToken('MONPROF_WEB');

        $this->postJson('/api/user/reset_password', [
            'phone' => $user->phone,
            'type' => 'password',
            'password' => 'nouveau-mot-de-passe',
            'otp' => $this->otp($user->phone),
            'verification_id' => 'verif-1',
        ], $this->headers('device-A'))->assertOk();

        $this->assertSame(0, $user->fresh()->tokens()->count());
    }

    public function test_an_expired_code_changes_nothing(): void
    {
        $user = $this->student(device: 'ancien-telephone');
        $user->createToken('MONPROF_WEB');

        $this->postJson('/api/user/reset_password', [
            'phone' => $user->phone,
            'type' => 'phone_emei',
            'otp' => $this->otp($user->phone, expired: true),
            'verification_id' => 'verif-1',
        ], $this->headers('nouveau-telephone'))->assertStatus(400);

        $user->refresh();

        $this->assertSame('ancien-telephone', $user->user_phone_emei);
        $this->assertSame(1, $user->tokens()->count());
    }

    // ─── Utilitaires ──────────────────────────────────────────────────────

    private function headers(?string $device): array
    {
        return $device === null ? [] : ['HTTP_PHONE_EMEI' => $device];
    }

    private function student(
        string $email = 'eleve@monprof.test',
        string $phone = '690123456',
        int $ruleId = 2,
        ?string $device = 'device-A',
    ): User {
        $user = User::create([
            'name' => 'Élève',
            'email' => $email,
            'phone' => $phone,
            'password' => 'password',
            'rule_id' => $ruleId,
            'user_phone_emei' => $device,
        ]);

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    private function otp(string $phone, bool $expired = false): string
    {
        OTP::create([
            'phone' => $phone,
            'otp' => '1234',
            'verification_id' => 'verif-1',
            'is_used' => false,
            'expired_at' => $expired ? now()->subMinute() : now()->addMinutes(10),
        ]);

        return '1234';
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('rule_id')->default(2);
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('user_phone_emei')->nullable();
            $table->string('fcm_token')->nullable();
            $table->string('refresh_token')->nullable();
            $table->string('profile_image')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('otps', function (Blueprint $table): void {
            $table->id();
            $table->string('phone');
            $table->string('otp');
            $table->string('verification_id')->nullable();
            $table->string('signature')->nullable();
            $table->boolean('is_used')->default(false);
            $table->dateTime('expired_at')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
}
