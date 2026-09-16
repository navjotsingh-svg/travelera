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
            'stripe.enabled' => false,
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

    public function test_book_page_shows_baggage_and_seat_sheets(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/air/offers/off_0001')) {
                return Http::response(['data' => $this->offerPayload(withServices: true)], 200);
            }

            if (str_contains($request->url(), '/air/seat_maps')) {
                return Http::response(['data' => [$this->seatMapPayload()]], 200);
            }

            return Http::response(['errors' => [['message' => 'Unexpected Duffel URL: '.$request->url()]]], 500);
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/flights/offers/off_0001/book');

        $response->assertOk();
        $response->assertSee('Add baggage');
        $response->assertSee('Select seats');
        $response->assertSee('Check-in bag');
        $response->assertSee('Cabin bag');
    }

    public function test_authenticated_user_can_book_a_duffel_offer_with_extras(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/air/offers/off_0001')) {
                return Http::response(['data' => $this->offerPayload(withServices: true)], 200);
            }

            if (str_contains($request->url(), '/air/seat_maps')) {
                return Http::response(['data' => [$this->seatMapPayload()]], 200);
            }

            if (str_contains($request->url(), '/air/orders') && $request->method() === 'POST') {
                return Http::response([
                    'data' => [
                        'id' => 'ord_0001',
                        'booking_reference' => 'ABC123',
                        'total_amount' => '495.00',
                        'total_currency' => 'GBP',
                    ],
                ], 200);
            }

            return Http::response(['errors' => [['message' => 'Unexpected Duffel URL: '.$request->url()]]], 500);
        });

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
            'services' => [
                ['id' => 'ase_bag_1', 'quantity' => 1],
                ['id' => 'ase_seat_12A', 'quantity' => 1],
            ],
            'payment_choice' => 'pay_now',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('bookings', [
            'provider' => 'duffel',
            'duffel_offer_id' => 'off_0001',
            'duffel_order_id' => 'ord_0001',
            'airline_pnr' => 'ABC123',
            'guest_email' => 'jane@example.com',
        ]);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/air/orders')) {
                return false;
            }

            $payload = $request->data()['data'] ?? [];
            $serviceIds = collect($payload['services'] ?? [])->pluck('id')->all();

            return ($payload['payments'][0]['amount'] ?? null) === '495.00'
                && in_array('ase_bag_1', $serviceIds, true)
                && in_array('ase_seat_12A', $serviceIds, true);
        });
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

    private function offerPayload(bool $withServices = false): array
    {
        $departure = now()->addDays(14)->setTime(9, 30)->toIso8601String();
        $arrival = now()->addDays(14)->setTime(12, 15)->toIso8601String();

        $payload = [
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
                    'id' => 'seg_0001',
                    'departing_at' => $departure,
                    'arriving_at' => $arrival,
                    'duration' => 'PT7H45M',
                    'origin' => ['iata_code' => 'LHR', 'name' => 'Heathrow'],
                    'destination' => ['iata_code' => 'JFK', 'name' => 'John F Kennedy'],
                    'operating_carrier' => ['name' => 'British Airways', 'iata_code' => 'BA'],
                    'marketing_carrier' => ['name' => 'British Airways', 'iata_code' => 'BA'],
                    'marketing_carrier_flight_number' => '178',
                    'passengers' => [[
                        'passenger_id' => 'pas_0001',
                        'cabin_class' => 'economy',
                        'baggages' => [
                            ['type' => 'carry_on', 'quantity' => 1],
                            ['type' => 'checked', 'quantity' => 1],
                        ],
                    ]],
                ]],
            ]],
        ];

        if ($withServices) {
            $payload['available_services'] = [[
                'id' => 'ase_bag_1',
                'type' => 'baggage',
                'total_amount' => '25.00',
                'total_currency' => 'GBP',
                'maximum_quantity' => 2,
                'passenger_ids' => ['pas_0001'],
                'segment_ids' => ['seg_0001'],
                'metadata' => [
                    'type' => 'checked',
                    'maximum_weight_kg' => 23,
                ],
            ]];
        }

        return $payload;
    }

    private function seatMapPayload(): array
    {
        return [
            'id' => 'map_0001',
            'segment_id' => 'seg_0001',
            'slice_id' => 'sli_0001',
            'cabins' => [[
                'cabin_class' => 'economy',
                'aisles' => 1,
                'rows' => [[
                    'sections' => [[
                        'elements' => [[
                            'type' => 'seat',
                            'designator' => '12A',
                            'name' => 'Window',
                            'disclosures' => [],
                            'available_services' => [[
                                'id' => 'ase_seat_12A',
                                'passenger_id' => 'pas_0001',
                                'total_amount' => '20.00',
                                'total_currency' => 'GBP',
                            ]],
                        ]],
                    ]],
                ]],
            ]],
        ];
    }
}
