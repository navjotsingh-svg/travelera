<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, EmailOtpService $otps): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $email = $request->string('email')->lower()->toString();
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            RateLimiter::hit($request->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'No account found for this email. Sign up to get started.',
            ]);
        }

        RateLimiter::clear($request->throttleKey());

        $otps->send($email, EmailOtp::PURPOSE_LOGIN);

        $request->session()->put('otp', [
            'email' => $email,
            'purpose' => EmailOtp::PURPOSE_LOGIN,
            'remember' => $request->boolean('remember'),
        ]);

        return redirect()->route('otp.prompt')->with('status', 'We sent a 6-digit code to '.$email.'.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
