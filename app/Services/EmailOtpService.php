<?php

namespace App\Services;

use App\Mail\EmailOtpMail;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class EmailOtpService
{
    public function send(string $email, string $purpose, array $payload = []): void
    {
        $email = strtolower($email);

        $latest = EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->latest()
            ->first();

        if ($latest && $latest->created_at->gt(now()->subSeconds((int) config('otp.resend_seconds', 45)))) {
            throw ValidationException::withMessages([
                'otp' => 'Please wait a moment before requesting another code.',
            ]);
        }

        EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), (int) config('otp.length', 6), '0', STR_PAD_LEFT);

        EmailOtp::create([
            'email' => $email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'payload' => $payload ?: null,
            'expires_at' => now()->addMinutes((int) config('otp.expire_minutes', 10)),
        ]);

        Mail::to($email)->send(new EmailOtpMail($code, $purpose));
    }

    public function verify(string $email, string $purpose, string $code): EmailOtp
    {
        $email = strtolower($email);

        $otp = EmailOtp::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $otp || $otp->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'otp' => 'That code has expired. Request a new one.',
            ]);
        }

        if ($otp->attempts >= (int) config('otp.max_attempts', 5)) {
            $otp->update(['consumed_at' => now()]);

            throw ValidationException::withMessages([
                'otp' => 'Too many attempts. Request a new code.',
            ]);
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            throw ValidationException::withMessages([
                'otp' => 'That code is not valid.',
            ]);
        }

        $otp->update(['consumed_at' => now()]);

        return $otp->refresh();
    }
}
