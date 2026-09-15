<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\TravelPackage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(Request $request): View
    {
        $packages = TravelPackage::query()
            ->with('destination')
            ->when($request->filled('destination'), function ($query) use ($request) {
                $query->whereHas('destination', fn ($destination) => $destination->where('city', $request->string('destination')));
            })
            ->orderBy('price')
            ->paginate(8)
            ->withQueryString();

        $cities = Destination::query()->orderBy('city')->pluck('city')->unique()->values();

        return view('packages.index', compact('packages', 'cities'));
    }

    public function show(TravelPackage $travelPackage): View
    {
        $travelPackage->load('destination');

        return view('packages.show', [
            'package' => $travelPackage,
        ]);
    }
}
