<?php

namespace App\Http\Controllers;

use App\Models\Cab;
use App\Models\Destination;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CabController extends Controller
{
    public function index(Request $request): View
    {
        $cabs = Cab::query()
            ->when($request->filled('city'), fn ($query) => $query->where('city', $request->string('city')))
            ->when($request->boolean('available_only', false), fn ($query) => $query->where('is_available', true))
            ->orderBy('base_fare')
            ->paginate(9)
            ->withQueryString();

        $cities = Destination::query()->orderBy('city')->pluck('city')->unique()->values();

        return view('cabs.index', compact('cabs', 'cities'));
    }

    public function show(Cab $cab): View
    {
        return view('cabs.show', compact('cab'));
    }
}
