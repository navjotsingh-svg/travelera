<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AirportSuggestTest extends TestCase
{
    use RefreshDatabase;
    public function test_airport_suggestions_match_typed_city_names(): void
    {
        config(['duffel.access_token' => null]);

        $response = $this->getJson('/airports/suggest?q=delhi');

        $response->assertOk();
        $response->assertJsonFragment(['code' => 'DEL']);
        $this->assertStringContainsString('Delhi', $response->json('0.city') ?? $response->json('0.name') ?? '');
    }

    public function test_home_page_uses_typeable_flight_fields(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Going to?');
        $response->assertSee('Search by place / airport');
        $response->assertDontSee('DEL —', false);
    }
}
