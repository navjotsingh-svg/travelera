<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\TravelPackage;
use App\Services\Duffel\DuffelFlightService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(DuffelFlightService $duffel): View
    {
        $cities = Destination::query()
            ->orderBy('city')
            ->pluck('city')
            ->unique()
            ->values();

        $destinationOrder = ['London', 'Paris', 'Dubai', 'Bali'];
        $destinations = Destination::query()
            ->whereIn('city', $destinationOrder)
            ->get()
            ->sortBy(fn ($destination) => array_search($destination->city, $destinationOrder, true))
            ->values();

        if ($destinations->isEmpty()) {
            $destinations = Destination::query()->where('is_featured', true)->latest()->take(4)->get();
        }

        $heroImages = [
            'London' => 'https://images.unsplash.com/photo-1486299267070-83823f5448dd?auto=format&fit=crop&w=1400&q=80',
            'Paris' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=1400&q=80',
            'Dubai' => 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?auto=format&fit=crop&w=1400&q=80',
            'Bali' => 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=1400&q=80',
        ];

        $destinations->transform(function ($destination) use ($heroImages) {
            if (isset($heroImages[$destination->city])) {
                $destination->image = $heroImages[$destination->city];
            }

            return $destination;
        });

        return view('home', [
            'destinations' => $destinations,
            'packages' => TravelPackage::query()->with('destination')->where('is_featured', true)->take(4)->get(),
            'cities' => $cities,
            'airports' => $duffel->airports(),
        ]);
    }
}
