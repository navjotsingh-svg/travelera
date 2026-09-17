<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckoutAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'booking_id',
        'offer_id',
        'airline',
        'flight_number',
        'origin',
        'destination',
        'amount',
        'currency',
        'stripe_checkout_session_id',
        'paypal_order_id',
        'payload',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payload' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function scopeAbandoned(Builder $query): Builder
    {
        return $query->where('status', 'started')
            ->where('created_at', '<=', now()->subMinutes(30));
    }

    public function title(): string
    {
        $route = trim(($this->origin ?? '').' → '.($this->destination ?? ''), ' →');

        return trim(($this->airline ?? '').' '.($this->flight_number ?? '')).($route ? ' · '.$route : '');
    }
}
