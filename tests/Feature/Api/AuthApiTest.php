<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_register_success(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Maria Wattimena',
            'email' => 'maria@gmail.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'no_hp' => '081234567890',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Registrasi berhasil.')
            ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);
    }

    public function test_register_validation_error(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'A',
            'email' => 'bukan-email',
            'password' => '123',
        ])->assertStatus(422);
    }

    public function test_login_success_and_logout(): void
    {
        $this->createUser([
            'email' => 'login@gmail.com',
            'password' => 'password123',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@gmail.com',
            'password' => 'password123',
        ]);

        $login->assertOk()
            ->assertJsonPath('message', 'Login berhasil.')
            ->assertJsonStructure(['token']);

        $token = $login->json('token');

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout berhasil.');
    }

    public function test_login_invalid_credentials(): void
    {
        $this->createUser(['email' => 'x@gmail.com', 'password' => 'password123']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'x@gmail.com',
            'password' => 'salah',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Email atau kata sandi salah.');
    }

    public function test_forgot_password_unknown_email(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'tidakada@gmail.com',
        ])->assertNotFound();
    }

    public function test_otp_and_reset_password_flow(): void
    {
        $this->createProfilSekolah();
        $this->createUser(['email' => 'otp@gmail.com', 'password' => 'password123']);

        $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'otp@gmail.com',
        ])->assertOk();

        $otp = \App\Models\OtpCode::where('email', 'otp@gmail.com')->latest()->first()->otp;

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => 'otp@gmail.com',
            'otp' => $otp,
        ])->assertOk();

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'otp@gmail.com',
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])->assertOk()
            ->assertJsonPath('message', 'Kata sandi berhasil diperbarui.');

        $this->postJson('/api/v1/auth/login', [
            'email' => 'otp@gmail.com',
            'password' => 'passwordbaru',
        ])->assertOk();
    }
}
