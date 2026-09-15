<?php

namespace App\Http\Controllers;

use App\Services\Duffel\DuffelFlightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AirportController extends Controller
{
    public function suggest(Request $request, DuffelFlightService $duffel): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'max:80'],
        ]);

        return response()->json($duffel->suggestAirports($request->string('q')->toString()));
    }
}
