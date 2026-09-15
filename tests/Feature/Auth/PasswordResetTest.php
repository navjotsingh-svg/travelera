<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_otp_can_be_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('otp.prompt'));

        Mail::assertSent(EmailOtpMail::class, function (EmailOtpMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_password_can_be_reset_with_valid_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        $code = $this->lastOtpCode();

        $this->post('/otp', ['otp' => $code])
            ->assertRedirect(route('password.reset'));

        $this->post('/reset-password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_reset_password_screen_requires_verified_otp(): void
    {
        $this->get('/reset-password')->assertRedirect(route('password.request'));
    }

    private function lastOtpCode(): string
    {
        $code = null;

        Mail::assertSent(EmailOtpMail::class, function (EmailOtpMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $this->assertNotNull($code);

        return $code;
    }
}
