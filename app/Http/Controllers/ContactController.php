<?php

namespace App\Http\Controllers;

use App\Mail\ContactQueryMail;
use App\Models\ContactQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('contact');
    }

    public function store(Request $request): RedirectResponse
    {
        $intent = $request->string('intent')->toString() ?: 'contact';

        if ($intent === 'newsletter') {
            $validated = $request->validate([
                'email' => ['required', 'email', 'max:180'],
            ]);

            $query = ContactQuery::query()->create([
                'intent' => 'newsletter',
                'name' => null,
                'email' => $validated['email'],
                'phone' => null,
                'message' => 'Newsletter signup',
                'status' => 'new',
                'ip_address' => $request->ip(),
            ]);

            $this->notifySupport($query);

            return back()->with([
                'status' => 'You are on the Travelera list. We will send trip ideas to your inbox.',
                'status_title' => 'Welcome aboard',
                'status_kind' => 'newsletter',
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:2000'],
            'intent' => ['nullable', 'in:contact,enquiry,visa'],
        ]);

        $query = ContactQuery::query()->create([
            'intent' => in_array($intent, ['contact', 'enquiry', 'visa'], true) ? $intent : 'contact',
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => 'new',
            'ip_address' => $request->ip(),
        ]);

        $this->notifySupport($query);

        [$title, $message] = match ($query->intent) {
            'enquiry' => [
                'Request received',
                'Thanks. A travel specialist will call you about a private or tailored trip.',
            ],
            'visa' => [
                'Visa enquiry sent',
                'Thanks. We have your visa enquiry and will reply shortly.',
            ],
            default => [
                'Message sent',
                'Thanks. We have your message and will reply shortly.',
            ],
        };

        return back()->with([
            'status' => $message,
            'status_title' => $title,
            'status_kind' => $query->intent,
        ]);
    }

    private function notifySupport(ContactQuery $query): void
    {
        $to = (string) config('mail.support_address', config('mail.from.address'));

        if (! filled($to)) {
            return;
        }

        try {
            Mail::to($to)->send(new ContactQueryMail($query));
        } catch (\Throwable $exception) {
            Log::warning('Failed to send contact query email', [
                'query_id' => $query->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
