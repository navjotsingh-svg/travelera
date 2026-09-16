<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightSearch extends Model
{
    protected $fillable = [
        'user_id',
        'origin',
        'destination',
        'departure_date',
        'return_date',
        'cabin',
        'adults',
        'results_count',
        'had_error',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'departure_date' => 'date',
            'return_date' => 'date',
            'had_error' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
