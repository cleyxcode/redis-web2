<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_profile_requires_auth(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
    }

    public function test_show_and_update_profile_info(): void
    {
        $user = $this->createUser([
            'name' => 'Lama',
            'email' => 'lama@example.com',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('email', 'lama@example.com');

        $this->patchJson('/api/v1/profile/info', [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
            'no_hp' => '081111111111',
        ])->assertOk()
            ->assertJsonPath('message', 'Informasi akun berhasil diperbarui.')
            ->assertJsonPath('user.name', 'Nama Baru');
    }

    public function test_update_password_success_and_wrong_current(): void
    {
        $user = $this->createUser(['password' => 'password123']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/profile/password', [
            'current_password' => 'salah',
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Kata sandi saat ini tidak sesuai.');

        $this->patchJson('/api/v1/profile/password', [
            'current_password' => 'password123',
            'password' => 'passwordbaru',
            'password_confirmation' => 'passwordbaru',
        ])->assertOk()
            ->assertJsonPath('message', 'Kata sandi berhasil diperbarui.');

        $user->refresh();
        $this->assertTrue(Hash::check('passwordbaru', $user->password));
    }
}
