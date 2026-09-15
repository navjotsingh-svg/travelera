<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Cab extends Model
{
    protected $fillable = [
        'name',
        'vehicle_type',
        'capacity',
        'city',
        'price_per_km',
        'base_fare',
        'image',
        'description',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'price_per_km' => 'decimal:2',
            'base_fare' => 'decimal:2',
        ];
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }
}
