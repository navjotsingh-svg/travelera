<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContactQuery extends Model
{
    protected $fillable = [
        'intent',
        'name',
        'email',
        'phone',
        'message',
        'status',
        'ip_address',
    ];

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('status', 'new');
    }

    public function intentLabel(): string
    {
        return match ($this->intent) {
            'enquiry' => 'Trip enquiry',
            'newsletter' => 'Newsletter',
            'visa' => 'Visa enquiry',
            default => 'Contact',
        };
    }
}
