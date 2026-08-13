<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class WebAuthAndUserFlowTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_user_can_register_login_and_logout(): void
    {
        $this->post('/register', [
            'name' => 'Orang Tua Test',
            'email' => 'ortu@example.com',
            'no_hp' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'ortu@example.com',
            'role' => 'orangtua',
        ]);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();

        $this->post('/login', [
            'email' => 'ortu@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->createUser([
            'email' => 'ortu@example.com',
            'password' => 'password123',
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'ortu@example.com',
                'password' => 'salah',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_update_profil_akun_info_and_password(): void
    {
        $user = $this->createUser([
            'email' => 'lama@example.com',
            'password' => 'password123',
        ]);

        $this->actingAs($user)
            ->patch('/profil-akun/info', [
                'name' => 'Nama Updated',
                'email' => 'baru@example.com',
                'no_hp' => '089999999999',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('Nama Updated', $user->name);
        $this->assertSame('baru@example.com', $user->email);

        $this->actingAs($user)
            ->patch('/profil-akun/password', [
                'current_password' => 'password123',
                'password' => 'passwordbaru1',
                'password_confirmation' => 'passwordbaru1',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue(Hash::check('passwordbaru1', $user->password));
    }

    public function test_web_otp_forgot_password_flow(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $user = $this->createUser([
            'email' => 'reset@example.com',
            'password' => 'password123',
        ]);

        $this->post('/forgot-password', [
            'email' => 'reset@example.com',
        ])->assertRedirect(route('password.verify'));

        $otp = OtpCode::query()->where('email', 'reset@example.com')->latest('id')->first();
        $this->assertNotNull($otp);

        $this->withSession([
            'otp_email' => 'reset@example.com',
        ])->post('/verify-otp', [
            'otp' => $otp->otp,
        ])->assertRedirect(route('password.reset.form'));

        $this->withSession([
            'reset_verified_email' => 'reset@example.com',
        ])->post('/reset-password', [
            'password' => 'passwordbaru2',
            'password_confirmation' => 'passwordbaru2',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('passwordbaru2', $user->password));
    }

    public function test_authenticated_user_can_submit_pendaftaran_on_web(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();
        $user = $this->createUser();
        $this->createInfoPendaftaran($admin);

        $this->actingAs($user)
            ->post('/pendaftaran', [
                'nama_anak' => 'Anak Web',
                'tempat_lahir' => 'Ambon',
                'tanggal_lahir' => '2019-03-03',
                'jenis_kelamin' => 'L',
                'agama' => 'Kristen',
                'alamat' => 'Jl. Sekolah',
                'no_hp' => '081234567890',
                'dokumen_kk' => UploadedFile::fake()->image('kk.jpg'),
                'dokumen_akta' => UploadedFile::fake()->image('akta.jpg'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pendaftaran', [
            'user_id' => $user->id,
            'nama_anak' => 'Anak Web',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get('/pendaftaran/riwayat')
            ->assertOk()
            ->assertSee('Anak Web');
    }

    public function test_guest_cannot_post_pendaftaran(): void
    {
        $this->post('/pendaftaran', [
            'nama_anak' => 'Anonim',
        ])->assertRedirect('/login');
    }

    public function test_register_validation_errors(): void
    {
        $this->from('/register')
            ->post('/register', [
                'name' => '',
                'email' => 'bukan-email',
                'no_hp' => '',
                'password' => '123',
                'password_confirmation' => '456',
            ])
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['name', 'email', 'no_hp', 'password']);

        $this->assertSame(0, User::query()->count());
    }
}
