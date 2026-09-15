<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $intent = $request->string('intent')->toString() ?: 'contact';

        if ($intent === 'newsletter') {
            $request->validate([
                'email' => ['required', 'email'],
            ]);

            return back()->with('status', 'You are on the Travelera list. We will send trip ideas to your inbox.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $message = $intent === 'enquiry'
            ? 'Thanks. A travel specialist will call you about a private or tailored trip.'
            : 'Thanks. We have your message and will reply shortly.';

        return back()->with('status', $message);
    }
}
