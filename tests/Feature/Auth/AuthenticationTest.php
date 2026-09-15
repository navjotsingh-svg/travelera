<?php

namespace Tests\Feature\Auth;

use App\Mail\EmailOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_with_email_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
        ])->assertRedirect(route('otp.prompt'));

        $this->assertGuest();

        $code = $this->lastOtpCode();

        $this->post('/otp', ['otp' => $code])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_cannot_authenticate_with_an_invalid_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email]);

        $this->post('/otp', ['otp' => '000000'])->assertSessionHasErrors('otp');

        $this->assertGuest();
    }

    public function test_unknown_email_cannot_request_a_login_code(): void
    {
        Mail::fake();

        $this->from('/login')
            ->post('/login', ['email' => 'missing@example.com'])
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();
        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
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
