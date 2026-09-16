<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QueryController extends Controller
{
    public function index(Request $request): View
    {
        $queries = ContactQuery::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', $q)
                        ->orWhere('email', 'like', $q)
                        ->orWhere('phone', 'like', $q)
                        ->orWhere('message', 'like', $q);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('intent'), fn ($query) => $query->where('intent', $request->string('intent')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $totals = [
            'all' => ContactQuery::query()->count(),
            'new' => ContactQuery::query()->unread()->count(),
            'today' => ContactQuery::query()->whereDate('created_at', today())->count(),
        ];

        return view('admin.queries.index', compact('queries', 'totals'));
    }

    public function show(ContactQuery $query): View
    {
        if ($query->status === 'new') {
            $query->update(['status' => 'read']);
        }

        return view('admin.queries.show', compact('query'));
    }

    public function update(Request $request, ContactQuery $query): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:new,read,replied,closed'],
        ]);

        $query->update($validated);

        return back()->with('status', 'Query status updated.');
    }

    public function destroy(ContactQuery $query): RedirectResponse
    {
        $query->delete();

        return redirect()->route('admin.queries.index')->with('status', 'Query deleted.');
    }
}
