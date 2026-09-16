<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'booking_reference',
        'bookable_type',
        'bookable_id',
        'provider',
        'duffel_offer_id',
        'duffel_order_id',
        'airline_pnr',
        'guest_name',
        'guest_email',
        'guest_phone',
        'travelers',
        'travel_date',
        'check_in',
        'check_out',
        'pickup_location',
        'drop_location',
        'distance_km',
        'cabin_class',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'notes',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'travel_date' => 'date',
            'check_in' => 'date',
            'check_out' => 'date',
            'total_amount' => 'decimal:2',
            'snapshot' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            $booking->booking_reference ??= 'TRAV-'.strtoupper(Str::random(8));
            $booking->status ??= 'confirmed';
            $booking->payment_status ??= 'paid';
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookable(): MorphTo
    {
        return $this->morphTo();
    }

    public function typeLabel(): string
    {
        return match ($this->bookable_type) {
            Flight::class => 'Flight',
            Hotel::class => 'Hotel',
            Cab::class => 'Cab',
            TravelPackage::class => 'Package',
            default => $this->provider === 'duffel' ? 'Flight' : 'Booking',
        };
    }

    public function title(): string
    {
        if ($this->provider === 'duffel') {
            $snapshot = $this->snapshot ?? [];

            if (! empty($snapshot['airline'])) {
                return trim(($snapshot['airline'] ?? '').' '.($snapshot['flight_number'] ?? '')).' · '.($snapshot['origin'] ?? '').' → '.($snapshot['destination'] ?? '');
            }
        }

        $item = $this->bookable;

        if (! $item) {
            return 'Unavailable listing';
        }

        return match ($this->bookable_type) {
            Flight::class => $item->airline.' '.$item->flight_number,
            Hotel::class => $item->name,
            Cab::class => $item->name.' · '.$item->city,
            TravelPackage::class => $item->title,
            default => 'Booking',
        };
    }

    public function departureAt(): ?\Carbon\Carbon
    {
        return $this->snapshotDateTime('departure_at')
            ?? optional($this->bookable)->departure_at;
    }

    public function arrivalAt(): ?\Carbon\Carbon
    {
        return $this->snapshotDateTime('arrival_at')
            ?? optional($this->bookable)->arrival_at;
    }

    public function flightDurationLabel(): ?string
    {
        $duration = $this->snapshot['duration'] ?? null;

        return filled($duration) ? (string) $duration : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function itinerarySlices(): array
    {
        $slices = $this->snapshot['slices'] ?? [];

        if (! is_array($slices) || $slices === []) {
            $departure = $this->departureAt();
            $arrival = $this->arrivalAt();

            if (! $departure && ! $arrival) {
                return [];
            }

            return [[
                'origin' => $this->snapshot['origin'] ?? '',
                'destination' => $this->snapshot['destination'] ?? '',
                'duration' => $this->flightDurationLabel(),
                'segments' => [[
                    'airline' => $this->snapshot['airline'] ?? '',
                    'flight_number' => $this->snapshot['flight_number'] ?? '',
                    'origin' => $this->snapshot['origin'] ?? '',
                    'destination' => $this->snapshot['destination'] ?? '',
                    'departure_at' => optional($departure)?->toIso8601String(),
                    'arrival_at' => optional($arrival)?->toIso8601String(),
                    'duration' => $this->flightDurationLabel(),
                ]],
            ]];
        }

        return $slices;
    }

    private function snapshotDateTime(string $key): ?\Carbon\Carbon
    {
        $value = $this->snapshot[$key] ?? null;

        if (! filled($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
