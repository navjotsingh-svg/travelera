<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgentChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'agent.enabled' => true,
            'agent.openai.api_key' => null,
            'duffel.access_token' => 'duffel_test_123',
            'duffel.base_url' => 'https://api.duffel.com',
            'duffel.version' => 'v2',
            'duffel.timeout' => 10,
            'duffel.payment_type' => 'balance',
        ]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    public function test_agent_page_loads(): void
    {
        $this->get(route('agent.chat'))
            ->assertOk()
            ->assertSee('Find, refine, then book yourself')
            ->assertSee('Confirm in chat', false);
    }

    public function test_agent_asks_for_missing_details(): void
    {
        $response = $this->postJson(route('agent.chat.store'), [
            'message' => 'Find me the cheapest flight',
        ]);

        $response->assertOk()
            ->assertJsonPath('offers', [])
            ->assertJsonPath('intent.incomplete', true);

        $this->assertStringContainsString('flying from and to', $response->json('reply'));
        $this->assertStringContainsString('payment stays under your control', strtolower($response->json('reply')));
    }

    public function test_agent_returns_cheapest_and_fastest_book_links_without_charging(): void
    {
        $date = now()->addDays(21)->toDateString();

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/air/offer_requests')) {
                return Http::response(['data' => $this->offerRequestPayload()], 200);
            }

            return Http::response(['errors' => [['message' => 'Unexpected Duffel URL: '.$request->url()]]], 500);
        });

        $response = $this->postJson(route('agent.chat.store'), [
            'message' => "Compare cheapest and fastest from London to New York on {$date}",
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent.origin', 'LON');
        $response->assertJsonPath('intent.destination', 'NYC');
        $response->assertJsonPath('intent.preference', 'both');
        $response->assertJsonPath('intent.incomplete', false);

        $offers = $response->json('offers');
        $this->assertNotEmpty($offers);
        $this->assertStringContainsString('/flights/offers/', $offers[0]['book_url']);
        $this->assertStringContainsString('/flights/offers/', $offers[0]['checkout_url']);
        $this->assertNotEmpty($offers[0]['why']);
        $this->assertStringContainsString('never charge', strtolower($response->json('reply')));
        $this->assertSame('Advise only — you confirm and pay on the booking page.', $response->json('disclaimer'));
        $this->assertNotEmpty($response->json('suggestions'));

        $ids = collect($offers)->pluck('id')->all();
        $this->assertContains('off_cheap', $ids);
        $this->assertContains('off_fast', $ids);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/air/offer_requests'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/air/orders'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'paypal'));
    }

    public function test_agent_refines_cached_results_to_nonstop_without_new_duffel_search(): void
    {
        $date = now()->addDays(21)->toDateString();

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/air/offer_requests')) {
                return Http::response(['data' => $this->offerRequestPayload()], 200);
            }

            return Http::response(['errors' => [['message' => 'Unexpected Duffel URL: '.$request->url()]]], 500);
        });

        $this->postJson(route('agent.chat.store'), [
            'message' => "Cheapest from London to New York on {$date}",
        ])->assertOk();

        Http::fake(function ($request) {
            return Http::response(['errors' => [['message' => 'Should not call Duffel again: '.$request->url()]]], 500);
        });

        $response = $this->postJson(route('agent.chat.store'), [
            'message' => 'Only nonstop',
            'action' => 'refine',
        ]);

        $response->assertOk();
        $response->assertJsonPath('intent.max_stops', 0);
        $ids = collect($response->json('offers'))->pluck('id')->all();
        $this->assertContains('off_fast', $ids);
        $this->assertNotContains('off_cheap', $ids);
        $this->assertStringContainsString('refined', strtolower($response->json('reply')));
    }

    public function test_agent_select_hands_off_to_checkout_without_creating_order(): void
    {
        $date = now()->addDays(21)->toDateString();
        $user = User::factory()->create();

        Http::fake(function ($request) {
            if (str_contains($request->url(), '/air/offer_requests')) {
                return Http::response(['data' => $this->offerRequestPayload()], 200);
            }

            return Http::response(['errors' => [['message' => 'Unexpected Duffel URL: '.$request->url()]]], 500);
        });

        $this->actingAs($user)->postJson(route('agent.chat.store'), [
            'message' => "Compare cheapest and fastest from London to New York on {$date}",
        ])->assertOk();

        $response = $this->actingAs($user)->postJson(route('agent.chat.store'), [
            'action' => 'select',
            'offer_id' => 'off_fast',
            'message' => 'Continue to book',
        ]);

        $response->assertOk();
        $response->assertJsonPath('selected.id', 'off_fast');
        $response->assertJsonPath('checkout.offer_id', 'off_fast');
        $response->assertJsonPath('checkout.auth_required', false);
        $response->assertJsonPath('selected.checkout_url', null);
        $this->assertStringContainsString('in the chat', strtolower($response->json('reply')));
        $this->assertStringContainsString('nothing is charged', strtolower($response->json('reply')));

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/air/orders'));
    }

    private function offerRequestPayload(): array
    {
        $day = now()->addDays(21);

        return [
            'id' => 'orq_agent_1',
            'passengers' => [
                ['id' => 'pas_0001', 'type' => 'adult'],
            ],
            'offers' => [
                $this->offer('off_cheap', '380.00', 'PT12H30M', $day->copy()->setTime(8, 0), $day->copy()->setTime(20, 30), 'AI', '101', stops: 1),
                $this->offer('off_fast', '520.00', 'PT7H15M', $day->copy()->setTime(9, 0), $day->copy()->setTime(16, 15), 'BA', '178', stops: 0),
                $this->offer('off_mid', '450.00', 'PT9H00M', $day->copy()->setTime(11, 0), $day->copy()->setTime(20, 0), 'VS', '3', stops: 0),
            ],
        ];
    }

    private function offer(
        string $id,
        string $amount,
        string $duration,
        $depart,
        $arrive,
        string $code,
        string $number,
        int $stops = 0,
    ): array {
        $segments = [[
            'id' => 'seg_'.$id.'_1',
            'departing_at' => $depart->toIso8601String(),
            'arriving_at' => $stops > 0 ? $depart->copy()->addHours(4)->toIso8601String() : $arrive->toIso8601String(),
            'duration' => $stops > 0 ? 'PT4H00M' : $duration,
            'origin' => ['iata_code' => 'LHR', 'name' => 'Heathrow', 'city_name' => 'London'],
            'destination' => $stops > 0
                ? ['iata_code' => 'DUB', 'name' => 'Dublin', 'city_name' => 'Dublin']
                : ['iata_code' => 'JFK', 'name' => 'John F Kennedy', 'city_name' => 'New York'],
            'operating_carrier' => ['name' => $code === 'BA' ? 'British Airways' : ($code === 'AI' ? 'Air India' : 'Virgin Atlantic'), 'iata_code' => $code],
            'marketing_carrier' => ['name' => $code === 'BA' ? 'British Airways' : ($code === 'AI' ? 'Air India' : 'Virgin Atlantic'), 'iata_code' => $code],
            'marketing_carrier_flight_number' => $number,
            'passengers' => [[
                'passenger_id' => 'pas_0001',
                'cabin_class' => 'economy',
                'baggages' => [],
            ]],
        ]];

        if ($stops > 0) {
            $segments[] = [
                'id' => 'seg_'.$id.'_2',
                'departing_at' => $depart->copy()->addHours(6)->toIso8601String(),
                'arriving_at' => $arrive->toIso8601String(),
                'duration' => 'PT6H00M',
                'origin' => ['iata_code' => 'DUB', 'name' => 'Dublin', 'city_name' => 'Dublin'],
                'destination' => ['iata_code' => 'JFK', 'name' => 'John F Kennedy', 'city_name' => 'New York'],
                'operating_carrier' => ['name' => 'Air India', 'iata_code' => 'AI'],
                'marketing_carrier' => ['name' => 'Air India', 'iata_code' => 'AI'],
                'marketing_carrier_flight_number' => '202',
                'passengers' => [[
                    'passenger_id' => 'pas_0001',
                    'cabin_class' => 'economy',
                    'baggages' => [],
                ]],
            ];
        }

        return [
            'id' => $id,
            'total_amount' => $amount,
            'total_currency' => 'GBP',
            'cabin_class' => 'economy',
            'expires_at' => now()->addMinutes(20)->toIso8601String(),
            'owner' => ['name' => $code === 'BA' ? 'British Airways' : ($code === 'AI' ? 'Air India' : 'Virgin Atlantic'), 'iata_code' => $code],
            'passengers' => [
                ['id' => 'pas_0001', 'type' => 'adult'],
            ],
            'slices' => [[
                'origin' => ['iata_code' => 'LHR', 'name' => 'Heathrow', 'city_name' => 'London'],
                'destination' => ['iata_code' => 'JFK', 'name' => 'John F Kennedy', 'city_name' => 'New York'],
                'duration' => $duration,
                'segments' => $segments,
            ]],
        ];
    }
}
