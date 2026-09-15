<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmailOtpController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('otp.email') || ! $request->session()->has('otp.purpose')) {
            return redirect()->route('login');
        }

        return view('auth.otp');
    }

    public function store(Request $request, EmailOtpService $otps): RedirectResponse
    {
        [$email, $purpose] = $this->pendingOtp($request);

        $request->validate([
            'otp' => ['required', 'digits:'.config('otp.length', 6)],
        ]);

        $record = $otps->verify($email, $purpose, $request->string('otp')->toString());

        return match ($purpose) {
            EmailOtp::PURPOSE_LOGIN => $this->completeLogin($request, $email),
            EmailOtp::PURPOSE_REGISTER => $this->completeRegister($request, $email, $record),
            EmailOtp::PURPOSE_PASSWORD_RESET => $this->completePasswordReset($request, $email),
            default => redirect()->route('login'),
        };
    }

    public function resend(Request $request, EmailOtpService $otps): RedirectResponse
    {
        [$email, $purpose] = $this->pendingOtp($request);

        $payload = [];

        if ($purpose === EmailOtp::PURPOSE_REGISTER) {
            $previous = EmailOtp::query()
                ->where('email', $email)
                ->where('purpose', $purpose)
                ->latest()
                ->first();

            $payload = $previous?->payload ?? [];
        }

        $otps->send($email, $purpose, $payload);

        return back()->with('status', 'We sent a new code to '.$email.'.');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function pendingOtp(Request $request): array
    {
        $email = $request->session()->get('otp.email');
        $purpose = $request->session()->get('otp.purpose');

        if (! $email || ! $purpose) {
            throw ValidationException::withMessages([
                'otp' => 'Start again to receive a new code.',
            ]);
        }

        return [$email, $purpose];
    }

    private function completeLogin(Request $request, string $email): RedirectResponse
    {
        $user = User::query()->where('email', $email)->firstOrFail();

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::login($user, (bool) $request->session()->get('otp.remember'));
        $request->session()->forget('otp');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function completeRegister(Request $request, string $email, EmailOtp $otp): RedirectResponse
    {
        $payload = $otp->payload ?? [];

        $user = User::create([
            'name' => $payload['name'] ?? 'Traveller',
            'email' => $email,
            'phone' => $payload['phone'] ?? null,
            'password' => $payload['password'] ?? '',
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        event(new Registered($user));

        Auth::login($user);
        $request->session()->forget('otp');
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    private function completePasswordReset(Request $request, string $email): RedirectResponse
    {
        $request->session()->forget('otp');
        $request->session()->put('password_reset_email', $email);

        return redirect()->route('password.reset')->with('status', 'Code confirmed. Choose a new password.');
    }
}
