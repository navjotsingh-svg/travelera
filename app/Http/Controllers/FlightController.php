<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Flight;
use App\Services\Duffel\DuffelException;
use App\Services\Duffel\DuffelFlightService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlightController extends Controller
{
    public function __construct(private readonly DuffelFlightService $duffel) {}

    public function index(Request $request): View
    {
        $airports = $this->duffel->airports();
        $cities = Destination::query()->orderBy('city')->pluck('city')->unique()->values();
        $live = $this->duffel->configured();
        $offers = collect();
        $error = null;
        $fromCode = $request->filled('from') ? $this->duffel->resolveLocation($request->string('from')->toString()) : null;
        $toCode = $request->filled('to') ? $this->duffel->resolveLocation($request->string('to')->toString()) : null;
        $searched = filled($fromCode) && filled($toCode) && $request->filled('date');

        if ($live && $searched) {
            $request->validate([
                'from' => ['required', 'string', 'max:80'],
                'to' => ['required', 'string', 'max:80'],
                'date' => ['required', 'date', 'after_or_equal:today'],
                'return_date' => ['nullable', 'date', 'after_or_equal:date'],
                'cabin' => ['nullable', 'in:economy,premium_economy,business,first'],
                'adults' => ['nullable', 'integer', 'min:1', 'max:9'],
            ]);

            if ($fromCode === $toCode) {
                $error = 'Origin and destination must be different airports.';
            } else {
                try {
                    $result = $this->duffel->search(
                        origin: $fromCode,
                        destination: $toCode,
                        departureDate: $request->date('date')->toDateString(),
                        returnDate: $request->filled('return_date') ? $request->date('return_date')->toDateString() : null,
                        cabinClass: $request->string('cabin')->toString() ?: 'economy',
                        adults: $request->integer('adults', 1),
                    );
                    $offers = collect($result['offers']);
                } catch (DuffelException $exception) {
                    $error = $exception->getMessage();
                }
            }
        }

        $flights = Flight::query()
            ->when($fromCode && ! $live, fn ($query) => $query->where('origin_code', $fromCode))
            ->when($toCode && ! $live, fn ($query) => $query->where('destination_code', $toCode))
            ->when($request->filled('cabin') && ! $live, fn ($query) => $query->where('cabin_class', $request->string('cabin')))
            ->when($request->filled('date') && ! $live, function ($query) use ($request) {
                $query->whereDate('departure_at', $request->date('date'));
            })
            ->orderBy('departure_at')
            ->paginate(8)
            ->withQueryString();

        return view('flights.index', compact('airports', 'cities', 'live', 'offers', 'error', 'searched', 'flights'));
    }

    public function offer(string $offer): View
    {
        abort_unless($this->duffel->configured(), 404);

        try {
            $flight = $this->duffel->offer($offer);
        } catch (DuffelException $exception) {
            abort(404, $exception->getMessage());
        }

        return view('flights.offer', compact('flight'));
    }

    public function show(Flight $flight): View
    {
        return view('flights.show', compact('flight'));
    }
}
