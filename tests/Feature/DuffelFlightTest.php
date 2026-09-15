<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DuffelFlightTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'duffel.access_token' => 'duffel_test_123',
            'duffel.base_url' => 'https://api.duffel.com',
            'duffel.version' => 'v2',
            'duffel.timeout' => 10,
            'duffel.payment_type' => 'balance',
        ]);
    }

    public function test_flight_search_uses_duffel_offer_requests(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/air/offer_requests')) {
                return Http::response(['data' => $this->offerRequestPayload()], 200);
            }

            return Http::response(['errors' => [['message' => 'Unexpected Duffel URL: '.$request->url()]]], 500);
        });

        $response = $this->get('/flights?from=LHR&to=JFK&date='.now()->addDays(14)->toDateString().'&cabin=economy&adults=1');

        $response->assertOk();
        $response->assertSee('British Airways');
        $response->assertSee('BA 178');
        $response->assertSee('Live fares via Duffel');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/air/offer_requests'));
    }

    public function test_flight_search_resolves_typed_city_names(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/air/offer_requests')) {
                return Http::response(['data' => $this->offerRequestPayload()], 200);
            }

            return Http::response(['errors' => [['message' => 'Unexpected Duffel URL: '.$request->url()]]], 500);
        });

        $response = $this->get('/flights?'.http_build_query([
            'from' => 'London',
            'to' => 'New York',
            'date' => now()->addDays(14)->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('British Airways');

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/air/offer_requests')) {
                return false;
            }

            $payload = $request->data();

            return ($payload['data']['slices'][0]['origin'] ?? null) === 'LON'
                && ($payload['data']['slices'][0]['destination'] ?? null) === 'NYC';
        });
    }

    public function test_authenticated_user_can_book_a_duffel_offer(): void
    {
        Http::fake([
            'https://api.duffel.com/air/offers/off_0001' => Http::response([
                'data' => $this->offerPayload(),
            ], 200),
            'https://api.duffel.com/air/orders' => Http::response([
                'data' => [
                    'id' => 'ord_0001',
                    'booking_reference' => 'ABC123',
                    'total_amount' => '450.00',
                    'total_currency' => 'GBP',
                ],
            ], 200),
        ]);

        $user = User::factory()->create([
            'name' => 'Jane Traveler',
            'email' => 'jane@example.com',
        ]);

        $response = $this->actingAs($user)->post('/flights/offers/off_0001/book', [
            'passengers' => [[
                'title' => 'ms',
                'given_name' => 'Jane',
                'family_name' => 'Traveler',
                'gender' => 'f',
                'born_on' => '1990-04-12',
                'email' => 'jane@example.com',
                'phone_number' => '9876543210',
            ]],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'provider' => 'duffel',
            'duffel_offer_id' => 'off_0001',
            'duffel_order_id' => 'ord_0001',
            'airline_pnr' => 'ABC123',
            'guest_email' => 'jane@example.com',
        ]);
    }

    private function offerRequestPayload(): array
    {
        return [
            'id' => 'orq_0001',
            'passengers' => [
                ['id' => 'pas_0001', 'type' => 'adult'],
            ],
            'offers' => [
                $this->offerPayload(),
            ],
        ];
    }

    private function offerPayload(): array
    {
        $departure = now()->addDays(14)->setTime(9, 30)->toIso8601String();
        $arrival = now()->addDays(14)->setTime(12, 15)->toIso8601String();

        return [
            'id' => 'off_0001',
            'total_amount' => '450.00',
            'total_currency' => 'GBP',
            'cabin_class' => 'economy',
            'expires_at' => now()->addMinutes(20)->toIso8601String(),
            'owner' => ['name' => 'British Airways', 'iata_code' => 'BA'],
            'passengers' => [
                ['id' => 'pas_0001', 'type' => 'adult'],
            ],
            'slices' => [[
                'origin' => ['iata_code' => 'LHR', 'name' => 'Heathrow'],
                'destination' => ['iata_code' => 'JFK', 'name' => 'John F Kennedy'],
                'duration' => 'PT7H45M',
                'segments' => [[
                    'departing_at' => $departure,
                    'arriving_at' => $arrival,
                    'duration' => 'PT7H45M',
                    'origin' => ['iata_code' => 'LHR', 'name' => 'Heathrow'],
                    'destination' => ['iata_code' => 'JFK', 'name' => 'John F Kennedy'],
                    'operating_carrier' => ['name' => 'British Airways', 'iata_code' => 'BA'],
                    'marketing_carrier' => ['name' => 'British Airways', 'iata_code' => 'BA'],
                    'marketing_carrier_flight_number' => '178',
                ]],
            ]],
        ];
    }
}
