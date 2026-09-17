<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedPassenger extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'given_name',
        'family_name',
        'gender',
        'born_on',
        'email',
        'phone_number',
        'passport_country',
        'passport_number',
        'passport_expiry',
    ];

    protected function casts(): array
    {
        return [
            'born_on' => 'date',
            'passport_expiry' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fullName(): string
    {
        return trim($this->given_name.' '.$this->family_name);
    }

    /**
     * @return array<string, mixed>
     */
    public function toFormArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'given_name' => $this->given_name,
            'family_name' => $this->family_name,
            'gender' => $this->gender,
            'born_on' => optional($this->born_on)?->toDateString(),
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'passport_country' => $this->passport_country,
            'passport_number' => $this->passport_number,
            'passport_expiry' => optional($this->passport_expiry)?->toDateString(),
            'label' => $this->fullName(),
        ];
    }
}
