<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TravelPackage extends Model
{
    protected $fillable = [
        'destination_id',
        'title',
        'slug',
        'duration_days',
        'price',
        'description',
        'includes',
        'image',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'includes' => 'array',
            'is_featured' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function bookings(): MorphMany
    {
        return $this->morphMany(Booking::class, 'bookable');
    }

    public function imageSrc(): string
    {
        $image = (string) $this->image;

        if ($image === '') {
            return '';
        }

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, '//')) {
            return $image;
        }

        return asset(ltrim($image, '/'));
    }
}
