<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Models\Setting;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class WhatsAppTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_normalize_local_and_international_numbers(): void
    {
        $this->assertSame('6281234567890', WhatsApp::normalize('0812-3456-7890'));
        $this->assertSame('6281234567890', WhatsApp::normalize('+62 812 3456 7890'));
        $this->assertSame('6281234567890', WhatsApp::normalize('81234567890'));
        $this->assertNull(WhatsApp::normalize(null));
        $this->assertNull(WhatsApp::normalize(''));
        $this->assertNull(WhatsApp::normalize('abc'));
    }

    public function test_url_builds_wa_me_link_with_message(): void
    {
        $url = WhatsApp::url('081234567890', 'Halo PPDB');

        $this->assertSame(
            'https://wa.me/6281234567890?text='.rawurlencode('Halo PPDB'),
            $url
        );
        $this->assertNull(WhatsApp::url(null));
    }

    public function test_school_contact_prefers_setting_then_profil(): void
    {
        $this->assertNull(WhatsApp::schoolContact());

        $this->createProfilSekolah(['kontak' => '081111111111']);
        $this->assertSame('081111111111', WhatsApp::schoolContact());

        Setting::query()->create([
            'key' => 'no_telp',
            'value' => '082222222222',
        ]);

        $this->assertSame('082222222222', WhatsApp::schoolContact());
    }
}
