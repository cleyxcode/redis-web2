<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTestData;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->createProfilSekolah();

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
