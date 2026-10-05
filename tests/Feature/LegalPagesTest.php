<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_pages_are_live_and_linked_from_the_footer(): void
    {
        $this->get('/terms-and-conditions')
            ->assertOk()
            ->assertSee('Terms & Conditions')
            ->assertSee('TRAVEL ERA LLC')
            ->assertSee('30 N Gould St Ste 4000')
            ->assertSee('Effective Date: November 1, 2025')
            ->assertSee('Last Updated: October 2, 2026')
            ->assertSee(route('legal.cancellation'), false)
            ->assertSee(route('legal.privacy'), false);

        $this->get('/cancellation-refund-policy')
            ->assertOk()
            ->assertSee('Cancellation & Refund Policy')
            ->assertSee('TRAVEL ERA LLC')
            ->assertSee('30 N Gould St Ste 4000')
            ->assertSee('booking@travelera.us')
            ->assertSee('24-Hour Cancellation')
            ->assertSee('[Insert fee schedule or applicable link when finalized.]');

        $this->get('/privacy-policy')
            ->assertOk()
            ->assertSee('Privacy Policy')
            ->assertSee('TRAVEL ERA LLC')
            ->assertSee('30 N Gould St Ste 4000')
            ->assertSee('Effective Date: November 1, 2025')
            ->assertSee('Cookies and Similar Technologies')
            ->assertSee('booking@travelera.us');

        $this->get('/disclaimer')
            ->assertOk()
            ->assertSee('Disclaimer')
            ->assertSee('TRAVEL ERA LLC')
            ->assertSee('30 N Gould St Ste 4000')
            ->assertSee('Effective Date: November 1, 2025')
            ->assertSee('FALCORE WORLD TRAVEL SERVICES PVT LTD (India)')
            ->assertSee(route('legal.cancellation'), false);

        $this->get('/privacy')->assertRedirect('/privacy-policy');
        $this->get('/flight-cancellation-policy')->assertRedirect('/cancellation-refund-policy');

        $this->get('/')
            ->assertOk()
            ->assertSee('LEGAL')
            ->assertSee(route('legal.terms'), false)
            ->assertSee(route('legal.cancellation'), false)
            ->assertSee(route('legal.privacy'), false)
            ->assertSee(route('legal.disclaimer'), false);
    }

    public function test_flight_cancellation_policy_link_does_not_open_visa_services(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('legal.cancellation'), '/').'"[^>]*>\s*Flight Cancellation Policy/',
            $html
        );
        $this->assertDoesNotMatchRegularExpression(
            '/href="'.preg_quote(route('visa'), '/').'"[^>]*>\s*Flight Cancellation Policy/',
            $html
        );
    }
}
