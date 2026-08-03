<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_valid_and_expired(): void
    {
        $valid = OtpCode::create([
            'email' => 'a@example.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(5),
            'used' => false,
        ]);

        $this->assertTrue($valid->isValid('123456'));
        $this->assertFalse($valid->isValid('000000'));
        $this->assertFalse($valid->isExpired());

        $expired = OtpCode::create([
            'email' => 'b@example.com',
            'otp' => '654321',
            'expires_at' => now()->subMinute(),
            'used' => false,
        ]);

        $this->assertTrue($expired->isExpired());
        $this->assertFalse($expired->isValid('654321'));
    }
}
