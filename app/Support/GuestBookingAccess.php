<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Str;

class GuestBookingAccess
{
    public static function grant(Booking $booking): void
    {
        $ids = collect(session('guest_booking_ids', []))
            ->push($booking->id)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(-20)
            ->values()
            ->all();

        session(['guest_booking_ids' => $ids]);
    }

    public static function issueToken(Booking $booking): string
    {
        $token = Str::random(40);
        $snapshot = $booking->snapshot ?? [];
        $snapshot['guest_access_hash'] = hash('sha256', $token);
        $booking->update(['snapshot' => $snapshot]);
        self::grant($booking);

        return $token;
    }

    public static function allows(?User $user, Booking $booking, ?string $token = null): bool
    {
        if ($user && $booking->user_id && (int) $booking->user_id === (int) $user->id) {
            return true;
        }

        $ids = collect(session('guest_booking_ids', []))->map(fn ($id) => (int) $id)->all();
        if (in_array((int) $booking->id, $ids, true)) {
            return true;
        }

        $hash = data_get($booking->snapshot, 'guest_access_hash');
        if (is_string($token) && $token !== '' && is_string($hash) && hash_equals($hash, hash('sha256', $token))) {
            self::grant($booking);

            return true;
        }

        return false;
    }
}
