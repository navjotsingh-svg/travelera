<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PayPal\PayPalPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class PayPalPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        config([
            'duffel.access_token' => 'duffel_test_123',
            'duffel.base_url' => 'https://api.duffel.com',
            'duffel.version' => 'v2',
            'duffel.timeout' => 10,
            'duffel.payment_type' => 'balance',
            'paypal.enabled' => true,
            'paypal.client_id' => 'paypal_client_123',
            'paypal.client_secret' => 'paypal_secret_123',
            'paypal.currency' => 'USD',
            'paypal.mode' => 'sandbox',
        ]);
    }

    public function test_flight_booking_redirects_to_paypal_checkout(): void
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

        $paypal = Mockery::mock(PayPalPaymentService::class);
        $paypal->shouldReceive('configured')->andReturn(true);
        $paypal->shouldReceive('createOrder')->once()->andReturn([
            'id' => 'PAYPAL-ORDER-123',
            'status' => 'CREATED',
            'approve_url' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-123',
            'raw' => [],
        ]);
        $this->app->instance(PayPalPaymentService::class, $paypal);

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

        $response->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-123');

        $this->assertDatabaseHas('bookings', [
            'provider' => 'duffel',
            'duffel_offer_id' => 'off_0001',
            'payment_status' => 'pending',
            'status' => 'pending',
            'paypal_order_id' => 'PAYPAL-ORDER-123',
        ]);
    }

    private function offerPayload(): array
    {
        $departure = now()->addDays(14)->setTime(9, 30)->toIso8601String();
        $arrival = now()->addDays(14)->setTime(12, 15)->toIso8601String();

        return [
            'id' => 'off_0001',
            'total_amount' => '450.00',
            'total_currency' => 'USD',
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
