<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AuthService::class);
        Mail::fake();
    }

    public function test_register_creates_user_and_token(): void
    {
        $result = $this->service->register([
            'name' => 'Maria Wattimena',
            'email' => 'maria@example.com',
            'password' => 'password123',
            'no_hp' => '081234567890',
        ]);

        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertSame('orangtua', $result['user']->role);
        $this->assertNotEmpty($result['token']);
        $this->assertDatabaseHas('users', ['email' => 'maria@example.com']);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        $user = $this->createUser([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $result = $this->service->login([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $this->assertSame($user->id, $result['user']->id);
        $this->assertNotEmpty($result['token']);
    }

    public function test_login_fails_for_invalid_password(): void
    {
        $this->createUser(['email' => 'wrong@example.com', 'password' => 'password123']);

        $this->expectException(ValidationException::class);
        $this->service->login([
            'email' => 'wrong@example.com',
            'password' => 'salah',
        ]);
    }

    public function test_send_otp_returns_false_for_unknown_email(): void
    {
        $this->assertFalse($this->service->sendOtp('tidakada@example.com'));
    }

    public function test_send_and_verify_otp_flow(): void
    {
        $this->createProfilSekolah();
        $this->createUser(['email' => 'otp@example.com']);

        $this->assertTrue($this->service->sendOtp('otp@example.com'));

        $otp = OtpCode::where('email', 'otp@example.com')->latest()->first();
        $this->assertNotNull($otp);
        $this->assertTrue($this->service->verifyOtp('otp@example.com', $otp->otp));
        $this->assertFalse($this->service->verifyOtp('otp@example.com', $otp->otp));
    }

    public function test_reset_password_updates_hash(): void
    {
        $user = $this->createUser(['email' => 'reset@example.com', 'password' => 'oldpassword']);

        $this->assertTrue($this->service->resetPassword('reset@example.com', 'newpassword'));
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword', $user->password));
    }

    public function test_logout_deletes_current_token(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('api-token');
        $user->withAccessToken($token->accessToken);

        $this->service->logout($user);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }
}
