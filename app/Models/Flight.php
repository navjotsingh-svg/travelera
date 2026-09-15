<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Flight extends Model
{
    protected $fillable = [
        'airline',
        'flight_number',
        'origin',
        'origin_code',
        'destination',
        'destination_code',
        'departure_at',
        'arrival_at',
        'duration_minutes',
        'cabin_class',
        'price',
        'seats_available',
        'image',
    ];

    protected function casts(): array
    {
        return [
            'departure_at' => 'datetime',
            'arrival_at' => 'datetime',
            'price' => 'decimal:2',
        ];
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function durationLabel(): string
    {
        $hours = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        return $hours.'h '.$minutes.'m';
    }
}
