<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Flight;
use App\Models\FlightSearch;
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
                $this->logSearch($request, $fromCode, $toCode, 0, true);
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
                    session([
                        'duffel_last_offers' => $offers
                            ->map(fn (array $offer) => [
                                'id' => $offer['id'],
                                'flight_key' => $offer['flight_key'] ?? null,
                                'airline' => $offer['airline'] ?? null,
                                'airline_logo' => $offer['airline_logo'] ?? null,
                                'flight_number' => $offer['flight_number'] ?? null,
                                'cabin_class' => $offer['cabin_class'] ?? null,
                                'fare_brand' => $offer['fare_brand'] ?? null,
                                'total_amount' => $offer['total_amount'] ?? null,
                                'total_currency' => $offer['total_currency'] ?? null,
                                'fare_features' => $offer['fare_features'] ?? [],
                                'supports_hold' => $offer['supports_hold'] ?? false,
                                'carbon_emissions' => $offer['carbon_emissions'] ?? null,
                            ])
                            ->values()
                            ->all(),
                    ]);
                    $this->logSearch($request, $fromCode, $toCode, $offers->count(), false);
                } catch (DuffelException $exception) {
                    $error = $exception->getMessage();
                    $this->logSearch($request, $fromCode, $toCode, 0, true);
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

        if (! $live && $searched) {
            $this->logSearch($request, $fromCode, $toCode, $flights->total(), false);
        }

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

        $fareOptions = collect(session('duffel_last_offers', []))
            ->filter(fn (array $item) => ($item['flight_key'] ?? null) === ($flight['flight_key'] ?? null))
            ->sortBy(fn (array $item) => (float) ($item['total_amount'] ?? 0))
            ->values();

        if ($fareOptions->isEmpty()) {
            $fareOptions = collect([[
                'id' => $flight['id'],
                'flight_key' => $flight['flight_key'] ?? null,
                'airline' => $flight['airline'] ?? null,
                'airline_logo' => $flight['airline_logo'] ?? null,
                'flight_number' => $flight['flight_number'] ?? null,
                'cabin_class' => $flight['cabin_class'] ?? null,
                'fare_brand' => $flight['fare_brand'] ?? null,
                'total_amount' => $flight['total_amount'] ?? null,
                'total_currency' => $flight['total_currency'] ?? null,
                'fare_features' => $flight['fare_features'] ?? [],
                'supports_hold' => $flight['supports_hold'] ?? false,
                'carbon_emissions' => $flight['carbon_emissions'] ?? null,
            ]]);
        } else {
            // Prefer detailed features for the currently opened offer.
            $fareOptions = $fareOptions->map(function (array $item) use ($flight) {
                if (($item['id'] ?? null) === $flight['id']) {
                    $item['fare_features'] = $flight['fare_features'] ?? ($item['fare_features'] ?? []);
                    $item['fare_brand'] = $flight['fare_brand'] ?? ($item['fare_brand'] ?? null);
                    $item['supports_hold'] = $flight['supports_hold'] ?? ($item['supports_hold'] ?? false);
                    $item['carbon_emissions'] = $flight['carbon_emissions'] ?? ($item['carbon_emissions'] ?? null);
                }

                return $item;
            });
        }

        return view('flights.offer', [
            'flight' => $flight,
            'fareOptions' => $fareOptions,
        ]);
    }

    public function show(Flight $flight): View
    {
        return view('flights.show', compact('flight'));
    }

    private function logSearch(Request $request, string $fromCode, string $toCode, int $resultsCount, bool $hadError): void
    {
        FlightSearch::query()->create([
            'user_id' => $request->user()?->id,
            'origin' => $fromCode,
            'destination' => $toCode,
            'departure_date' => $request->input('date'),
            'return_date' => $request->input('return_date'),
            'cabin' => $request->input('cabin', 'economy'),
            'adults' => $request->integer('adults', 1),
            'results_count' => $resultsCount,
            'had_error' => $hadError,
            'ip_address' => $request->ip(),
        ]);
    }
}
