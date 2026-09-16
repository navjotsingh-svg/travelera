<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Stripe\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Stripe\Checkout\Session;
use Tests\TestCase;

class StripePaymentTest extends TestCase
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
            'stripe.enabled' => true,
            'stripe.key' => 'pk_test_123',
            'stripe.secret' => 'sk_test_123',
            'stripe.currency' => 'gbp',
        ]);
    }

    public function test_flight_booking_redirects_to_stripe_checkout(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), '/air/offers/off_0001')) {
                return Http::response(['data' => $this->offerPayload()], 200);
            }

            if (str_contains($request->url(), '/air/seat_maps')) {
                return Http::response(['data' => []], 200);
            }

            return Http::response(['errors' => [['message' => 'Unexpected URL: '.$request->url()]]], 500);
        });

        $session = Session::constructFrom([
            'id' => 'cs_test_123',
            'object' => 'checkout.session',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
            'payment_status' => 'unpaid',
        ]);

        $stripe = Mockery::mock(StripePaymentService::class);
        $stripe->shouldReceive('configured')->andReturn(true);
        $stripe->shouldReceive('createCheckoutSession')->once()->andReturn($session);
        $this->app->instance(StripePaymentService::class, $stripe);

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
            'payment_choice' => 'pay_now',
        ]);

        $response->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');

        $this->assertDatabaseHas('bookings', [
            'provider' => 'duffel',
            'duffel_offer_id' => 'off_0001',
            'payment_status' => 'pending',
            'status' => 'pending',
            'stripe_checkout_session_id' => 'cs_test_123',
        ]);
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
            'available_services' => [],
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
                        'baggages' => [
                            ['type' => 'carry_on', 'quantity' => 1],
                        ],
                    ]],
                ]],
            ]],
        ];
    }
}
