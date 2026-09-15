<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HotelController extends Controller
{
    public function index(Request $request): View
    {
        $hotels = Hotel::query()
            ->when($request->filled('city'), fn ($query) => $query->where('city', 'like', '%'.$request->string('city').'%'))
            ->when($request->filled('stars'), fn ($query) => $query->where('star_rating', '>=', $request->integer('stars')))
            ->orderByDesc('guest_rating')
            ->paginate(9)
            ->withQueryString();

        $cities = Destination::query()->orderBy('city')->pluck('city')->unique()->values();

        return view('hotels.index', compact('hotels', 'cities'));
    }

    public function show(Hotel $hotel): View
    {
        $similar = Hotel::query()
            ->where('city', $hotel->city)
            ->where('id', '!=', $hotel->id)
            ->take(3)
            ->get();

        return view('hotels.show', compact('hotel', 'similar'));
    }
}
