<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlightSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $searches = FlightSearch::query()
            ->with('user')
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($q) {
                    $inner->where('origin', 'like', $q)
                        ->orWhere('destination', 'like', $q);
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $totals = [
            'all' => FlightSearch::query()->count(),
            'today' => FlightSearch::query()->whereDate('created_at', today())->count(),
            'week' => FlightSearch::query()->where('created_at', '>=', now()->subDays(7))->count(),
            'errors' => FlightSearch::query()->where('had_error', true)->count(),
        ];

        return view('admin.searches.index', compact('searches', 'totals'));
    }
}
