<?php

namespace App\Http\Controllers;

use App\Services\Agent\FlightAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentChatController extends Controller
{
    public function __construct(private readonly FlightAgentService $agent) {}

    public function show(): View
    {
        return view('agent.chat', [
            'welcome' => 'Hi — I find fares, then take passenger details here in chat. You still approve PayPal yourself. I never auto-charge.',
            'enabled' => (bool) config('agent.enabled', true),
            'auth' => auth()->check(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
            'action' => ['nullable', 'in:search,refine,select,reset'],
            'offer_id' => ['nullable', 'string', 'max:80'],
        ]);

        $action = $validated['action'] ?? null;
        $message = trim((string) ($validated['message'] ?? ''));

        if ($action === 'select' && filled($validated['offer_id'] ?? null) && $message === '') {
            $message = 'Continue to book';
        }

        if ($action === 'reset' && $message === '') {
            $message = 'Start over';
        }

        if ($message === '' || strlen($message) < 2) {
            return response()->json([
                'message' => 'Please enter a short travel request.',
            ], 422);
        }

        $previous = session('agent_intent', []);
        $pool = session('agent_offer_pool', []);

        $result = $this->agent->reply(
            message: $message,
            previousIntent: is_array($previous) ? $previous : [],
            offerPool: is_array($pool) ? $pool : [],
            action: $action,
            offerId: $validated['offer_id'] ?? null,
            user: $request->user(),
        );

        if (! empty($result['clear_session'])) {
            session()->forget(['agent_intent', 'agent_offer_pool', 'agent_history']);
        } else {
            session([
                'agent_intent' => $result['intent'] ?? [],
                'agent_offer_pool' => $result['offer_pool'] ?? [],
            ]);
        }

        if (! empty($result['session_offers'])) {
            session(['duffel_last_offers' => $result['session_offers']]);
        }

        if (empty($result['clear_session'])) {
            $history = collect(session('agent_history', []));
            $history->push([
                'role' => 'user',
                'content' => $message,
                'at' => now()->toIso8601String(),
            ]);
            $history->push([
                'role' => 'assistant',
                'content' => $result['reply'],
                'offers' => $result['offers'],
                'at' => now()->toIso8601String(),
            ]);
            session(['agent_history' => $history->take(-20)->values()->all()]);
        }

        return response()->json([
            'reply' => $result['reply'],
            'offers' => $result['offers'],
            'suggestions' => $result['suggestions'] ?? [],
            'selected' => $result['selected'] ?? null,
            'intent' => [
                'origin' => $result['intent']['origin'] ?? null,
                'destination' => $result['intent']['destination'] ?? null,
                'departure_date' => $result['intent']['departure_date'] ?? null,
                'return_date' => $result['intent']['return_date'] ?? null,
                'preference' => $result['intent']['preference'] ?? 'both',
                'max_stops' => $result['intent']['max_stops'] ?? null,
                'max_budget' => $result['intent']['max_budget'] ?? null,
                'departure_window' => $result['intent']['departure_window'] ?? 'any',
                'mode' => $result['intent']['mode'] ?? null,
                'incomplete' => (bool) ($result['intent']['incomplete'] ?? false),
            ],
            'disclaimer' => 'Advise only — you confirm and pay on the booking page.',
            'checkout' => $result['checkout'] ?? null,
        ]);
    }

    public function login(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->session()->put('url.intended', route('agent.chat'));

        return redirect()->route('login');
    }

    public function checkout(Request $request, \App\Services\FlightCheckoutService $checkout): JsonResponse
    {
        $validated = $request->validate([
            'offer_id' => ['required', 'string', 'max:80'],
            'passengers' => ['required', 'array', 'min:1', 'max:9'],
            'passengers.*.title' => ['required', 'in:mr,ms,mrs,miss,dr'],
            'passengers.*.given_name' => ['required', 'string', 'max:80'],
            'passengers.*.family_name' => ['required', 'string', 'max:80'],
            'passengers.*.gender' => ['required', 'in:m,f'],
            'passengers.*.born_on' => ['required', 'date', 'before:today'],
            'passengers.*.email' => ['required', 'email'],
            'passengers.*.phone_number' => ['required', 'string', 'max:30'],
            'payment_choice' => ['required', 'in:pay_now,hold'],
        ]);

        $result = $checkout->start($request->user(), $validated['offer_id'], $validated);

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'message' => $result['error'] ?? 'Checkout failed.',
            ], 422);
        }

        return response()->json([
            'reply' => $result['message'],
            'payment' => [
                'kind' => $result['kind'] ?? null,
                'approve_url' => $result['approve_url'] ?? null,
                'booking_url' => $result['booking_url'] ?? null,
                'booking_id' => $result['booking_id'] ?? null,
                'pnr' => $result['pnr'] ?? null,
                'total_amount' => $result['total_amount'] ?? null,
                'currency' => $result['currency'] ?? null,
            ],
            'disclaimer' => 'You approve payment yourself. The agent never auto-charges.',
        ]);
    }
}
