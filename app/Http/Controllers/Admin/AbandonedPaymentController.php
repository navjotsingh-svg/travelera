<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckoutAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AbandonedPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->string('filter')->toString() ?: 'abandoned';

        $attempts = CheckoutAttempt::query()
            ->with('user')
            ->when($filter === 'all', fn ($q) => $q->whereIn('status', ['started', 'abandoned']))
            ->when($filter !== 'all', fn ($q) => $q->abandoned())
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $count = CheckoutAttempt::abandoned()->count();

        return view('admin.abandoned.index', compact('attempts', 'count', 'filter'));
    }

    public function update(Request $request, CheckoutAttempt $checkoutAttempt): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:started,completed,abandoned,cancelled'],
        ]);

        $checkoutAttempt->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'completed' ? now() : $checkoutAttempt->completed_at,
        ]);

        return back()->with('status', 'Checkout attempt updated.');
    }
}
