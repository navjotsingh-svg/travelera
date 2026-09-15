<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailOtp;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request, EmailOtpService $otps): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
        ]);

        $email = $request->string('email')->lower()->toString();
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'No account found for this email. Sign up to get started.',
            ]);
        }

        $otps->send($email, EmailOtp::PURPOSE_PASSWORD_RESET);

        $request->session()->put('otp', [
            'email' => $email,
            'purpose' => EmailOtp::PURPOSE_PASSWORD_RESET,
        ]);

        return redirect()->route('otp.prompt')->with('status', 'We sent a 6-digit code to '.$email.'.');
    }
}
