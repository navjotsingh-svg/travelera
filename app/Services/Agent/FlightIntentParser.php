<?php

namespace App\Services\Agent;

use App\Services\Duffel\DuffelFlightService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FlightIntentParser
{
    public function __construct(private readonly DuffelFlightService $duffel) {}

    /**
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    public function parse(string $message, array $previous = []): array
    {
        $heuristic = $this->parseHeuristic($message, $previous);

        if ($this->openaiConfigured()) {
            try {
                $llm = $this->parseWithOpenAi($message, $previous);
                if ($llm !== null) {
                    return $this->mergeIntents($heuristic, $llm, 'openai');
                }
            } catch (\Throwable) {
                // Fall back to heuristic.
            }
        }

        return $heuristic;
    }

    /**
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    private function parseHeuristic(string $message, array $previous): array
    {
        $text = trim($message);
        $lower = Str::lower($text);

        $mode = $this->detectMode($lower, $previous);

        // Follow-up confirmations must not be treated as city pairs ("Continue to book").
        if ($mode === 'select' || $mode === 'reset') {
            return [
                'origin' => $previous['origin'] ?? null,
                'destination' => $previous['destination'] ?? null,
                'departure_date' => $previous['departure_date'] ?? null,
                'return_date' => $previous['return_date'] ?? null,
                'adults' => (int) ($previous['adults'] ?? 1),
                'cabin' => $previous['cabin'] ?? 'economy',
                'preference' => $previous['preference'] ?? 'both',
                'max_stops' => $previous['max_stops'] ?? null,
                'max_budget' => $previous['max_budget'] ?? null,
                'departure_window' => $previous['departure_window'] ?? 'any',
                'mode' => $mode,
                'selected_offer_id' => $this->extractSelection($lower, $previous),
                'route_changed' => false,
                'incomplete' => false,
                'missing' => [],
                'source' => 'heuristic',
            ];
        }

        $preference = $previous['preference'] ?? 'both';
        $mentionsCheap = (bool) preg_match('/\b(cheapest|lowest|affordable|inexpensive|cheap)\b/', $lower);
        $mentionsFast = (bool) preg_match('/\b(fastest|quickest|shortest|least\s+time)\b/', $lower);
        if (preg_match('/\b(best\s+value|both|compare)\b/', $lower) || ($mentionsCheap && $mentionsFast)) {
            $preference = 'both';
        } elseif ($mentionsFast) {
            $preference = 'fastest';
        } elseif ($mentionsCheap) {
            $preference = 'cheapest';
        }

        $cabin = $previous['cabin'] ?? 'economy';
        if (preg_match('/\b(premium\s*economy|premium_economy)\b/', $lower)) {
            $cabin = 'premium_economy';
        } elseif (preg_match('/\bbusiness\b/', $lower)) {
            $cabin = 'business';
        } elseif (preg_match('/\bfirst\b/', $lower)) {
            $cabin = 'first';
        } elseif (preg_match('/\beconomy\b/', $lower)) {
            $cabin = 'economy';
        }

        $adults = (int) ($previous['adults'] ?? 1);
        if (preg_match('/\b(\d)\s*(?:adults?|passengers?|travellers?|travelers?|pax)\b/', $lower, $m)) {
            $adults = max(1, min(9, (int) $m[1]));
        } elseif (preg_match('/\bfor\s+(\d)\b/', $lower, $m)) {
            $adults = max(1, min(9, (int) $m[1]));
        }

        $maxStops = array_key_exists('max_stops', $previous) ? $previous['max_stops'] : null;
        if (preg_match('/\b(non[\s-]?stop|direct\s+only|only\s+direct|direct\s+flights?)\b/', $lower)) {
            $maxStops = 0;
        } elseif (preg_match('/\b(?:max(?:imum)?|up\s+to)\s+(\d)\s+stops?\b/', $lower, $m)) {
            $maxStops = max(0, min(3, (int) $m[1]));
        } elseif (preg_match('/\b(\d)\s+stops?\s+ok\b/', $lower, $m)) {
            $maxStops = max(0, min(3, (int) $m[1]));
        } elseif (preg_match('/\b(any\s+stops?|with\s+stops?|connecting)\b/', $lower)) {
            $maxStops = null;
        }

        $maxBudget = array_key_exists('max_budget', $previous) ? $previous['max_budget'] : null;
        if (preg_match('/\b(?:under|below|max(?:imum)?|less\s+than|upto|up\s+to)\s*[$£€]?\s*(\d{2,5}(?:\.\d{1,2})?)\b/', $lower, $m)
            || preg_match('/\b(?:budget|cap)\s*(?:of|:)?\s*[$£€]?\s*(\d{2,5}(?:\.\d{1,2})?)\b/', $lower, $m)
        ) {
            $maxBudget = round((float) $m[1], 2);
        } elseif (preg_match('/\b(no\s+budget|any\s+price|clear\s+budget)\b/', $lower)) {
            $maxBudget = null;
        }

        $departureWindow = $previous['departure_window'] ?? 'any';
        if (preg_match('/\b(morning|before\s+noon|am\s+flight)\b/', $lower)) {
            $departureWindow = 'morning';
        } elseif (preg_match('/\b(afternoon)\b/', $lower)) {
            $departureWindow = 'afternoon';
        } elseif (preg_match('/\b(evening|night|after\s+6)\b/', $lower)) {
            $departureWindow = 'evening';
        } elseif (preg_match('/\b(any\s+time|all\s+day)\b/', $lower)) {
            $departureWindow = 'any';
        }

        $origin = $previous['origin'] ?? null;
        $destination = $previous['destination'] ?? null;
        $routeChanged = false;

        if (preg_match('/\b(?:from|origin)\s+([A-Za-z][A-Za-z\s\.\-]{1,40}?)\s+(?:to|→|->|into|for)\s+([A-Za-z][A-Za-z\s\.\-]{1,40})(?:\s|$|,|\.|!|\?)/iu', $text, $m)
            || preg_match('/\b([A-Za-z]{3}|[A-Za-z][A-Za-z\s\.\-]{2,30}?)\s+(?:to|→|->)\s+([A-Za-z]{3}|[A-Za-z][A-Za-z\s\.\-]{2,30})(?:\s|$|,|\.|!|\?)/iu', $text, $m)
        ) {
            $newOrigin = $this->resolvePlace(trim($m[1], " \t\n\r\0\x0B,."));
            $newDestination = $this->resolvePlace(trim($m[2], " \t\n\r\0\x0B,.!?"));
            if ($newOrigin && $newDestination) {
                $routeChanged = $newOrigin !== $origin || $newDestination !== $destination;
                $origin = $newOrigin;
                $destination = $newDestination;
            }
        }

        if ((! $origin || ! $destination) && preg_match_all('/\b([A-Z]{3})\b/', $text, $codes) && count($codes[1]) >= 2) {
            $origin = $origin ?: strtoupper($codes[1][0]);
            $destination = $destination ?: strtoupper($codes[1][1]);
            $routeChanged = true;
        }

        $departureDate = $previous['departure_date'] ?? null;
        $returnDate = $previous['return_date'] ?? null;
        $dateChanged = false;

        $parsedDeparture = $this->extractDate($text, $lower);
        if ($parsedDeparture) {
            $dateChanged = $parsedDeparture !== $departureDate;
            $departureDate = $parsedDeparture;
        }

        $wantsReturn = (bool) preg_match('/\b(return|round[\s-]?trip|coming\s+back|return\s+on)\b/', $lower);
        if ($wantsReturn) {
            if (preg_match('/\b(?:return(?:ing)?(?:\s+on)?|back\s+on)\s+(.+)$/iu', $text, $rm)) {
                $parsedReturn = $this->extractDate($rm[1], Str::lower($rm[1]));
                if ($parsedReturn) {
                    $returnDate = $parsedReturn;
                    $dateChanged = true;
                }
            }
        }

        if (! $wantsReturn && array_key_exists('return_date', $previous)) {
            $returnDate = $previous['return_date'];
        }

        $selectedOfferId = $previous['selected_offer_id'] ?? null;
        $extractedSelection = $this->extractSelection($lower, $previous);
        if ($extractedSelection) {
            $selectedOfferId = $extractedSelection;
            $mode = 'select';
        }

        $filtersTouched = $mentionsCheap || $mentionsFast
            || preg_match('/\b(non[\s-]?stop|direct|budget|under|below|morning|afternoon|evening|stops?)\b/', $lower);

        if ($mode === 'search' && ! $routeChanged && ! $dateChanged && $filtersTouched && filled($previous['origin'] ?? null) && filled($previous['departure_date'] ?? null)) {
            $mode = 'refine';
        }

        if ($routeChanged || ($dateChanged && $mode !== 'select')) {
            $mode = 'search';
        }

        $missing = [];
        if (! $origin) {
            $missing[] = 'origin';
        }
        if (! $destination) {
            $missing[] = 'destination';
        }
        if (! $departureDate) {
            $missing[] = 'departure_date';
        }

        return [
            'origin' => $origin,
            'destination' => $destination,
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
            'adults' => $adults,
            'cabin' => $cabin,
            'preference' => $preference,
            'max_stops' => $maxStops,
            'max_budget' => $maxBudget,
            'departure_window' => $departureWindow,
            'mode' => $mode,
            'selected_offer_id' => $selectedOfferId,
            'route_changed' => $routeChanged,
            'incomplete' => $missing !== [] && $mode !== 'reset',
            'missing' => $missing,
            'source' => 'heuristic',
        ];
    }

    /**
     * @param  array<string, mixed>  $previous
     */
    private function extractSelection(string $lower, array $previous): ?string
    {
        if (preg_match('/\b(?:book|select|choose|take|go\s+with)\s+(?:option\s*)?#?(\d)\b/', $lower, $m)) {
            return 'index:'.((int) $m[1]);
        }
        if (preg_match('/\b(?:book|select|choose|take)\s+(?:the\s+)?(cheapest|fastest)\b/', $lower, $m)) {
            return 'badge:'.$m[1];
        }
        if (preg_match('/\b(?:book|select|choose)\s+(off_[A-Za-z0-9_]+)\b/', $lower, $m)) {
            return $m[1];
        }

        $existing = $previous['selected_offer_id'] ?? null;

        return is_string($existing) && $existing !== '' ? $existing : null;
    }

    /**
     * @param  array<string, mixed>  $previous
     */
    private function detectMode(string $lower, array $previous): string
    {
        if (preg_match('/\b(start\s+over|new\s+search|reset|clear\s+search)\b/', $lower)) {
            return 'reset';
        }

        if (preg_match('/\b(continue\s+to\s+book|book\s+this|book\s+it|select\s+this|choose\s+this)\b/', $lower)
            || preg_match('/\b(book|select|choose|take|go\s+with)\s+(?:the\s+)?(?:cheapest|fastest|option|#?\d|off_)/', $lower)
        ) {
            return 'select';
        }

        if (preg_match('/\b(only|filter|refine|narrow|show\s+me|switch\s+to)\b/', $lower)
            && filled($previous['origin'] ?? null)
        ) {
            return 'refine';
        }

        return 'search';
    }

    /**
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>|null
     */
    private function parseWithOpenAi(string $message, array $previous): ?array
    {
        $system = <<<'PROMPT'
You extract flight search intent for Travelera. Reply with JSON only:
{
  "origin": "IATA/city or null",
  "destination": "IATA/city or null",
  "departure_date": "YYYY-MM-DD or null",
  "return_date": "YYYY-MM-DD or null",
  "adults": 1,
  "cabin": "economy|premium_economy|business|first",
  "preference": "cheapest|fastest|both",
  "max_stops": null or 0-3,
  "max_budget": null or number,
  "departure_window": "any|morning|afternoon|evening",
  "mode": "search|refine|select|reset",
  "selected_offer_id": null or "off_..." or "index:1" or "badge:cheapest|fastest"
}
Use previous context for follow-ups. Never invent payment/capture actions.
PROMPT;

        $payload = [
            'model' => config('agent.openai.model', 'gpt-4o-mini'),
            'temperature' => 0,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $system],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'today' => now()->toDateString(),
                        'previous' => $previous,
                        'message' => $message,
                    ], JSON_THROW_ON_ERROR),
                ],
            ],
        ];

        $response = Http::timeout((int) config('agent.openai.timeout', 20))
            ->withToken((string) config('agent.openai.api_key'))
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (! $response->successful()) {
            return null;
        }

        $content = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($content) || $content === '') {
            return null;
        }

        $data = json_decode($content, true);
        if (! is_array($data)) {
            return null;
        }

        $origin = isset($data['origin']) ? $this->resolvePlace((string) $data['origin']) : null;
        $destination = isset($data['destination']) ? $this->resolvePlace((string) $data['destination']) : null;
        $departure = $this->normalizeDate($data['departure_date'] ?? null);
        $return = $this->normalizeDate($data['return_date'] ?? null);
        $preference = in_array($data['preference'] ?? '', ['cheapest', 'fastest', 'both'], true)
            ? $data['preference']
            : 'both';
        $cabin = in_array($data['cabin'] ?? '', ['economy', 'premium_economy', 'business', 'first'], true)
            ? $data['cabin']
            : 'economy';
        $adults = max(1, min(9, (int) ($data['adults'] ?? 1)));
        $window = in_array($data['departure_window'] ?? '', ['any', 'morning', 'afternoon', 'evening'], true)
            ? $data['departure_window']
            : 'any';
        $mode = in_array($data['mode'] ?? '', ['search', 'refine', 'select', 'reset'], true)
            ? $data['mode']
            : 'search';
        $maxStops = array_key_exists('max_stops', $data) && $data['max_stops'] !== null
            ? max(0, min(3, (int) $data['max_stops']))
            : null;
        $maxBudget = isset($data['max_budget']) && is_numeric($data['max_budget'])
            ? round((float) $data['max_budget'], 2)
            : null;

        $missing = [];
        if (! $origin) {
            $missing[] = 'origin';
        }
        if (! $destination) {
            $missing[] = 'destination';
        }
        if (! $departure) {
            $missing[] = 'departure_date';
        }

        return [
            'origin' => $origin,
            'destination' => $destination,
            'departure_date' => $departure,
            'return_date' => $return,
            'adults' => $adults,
            'cabin' => $cabin,
            'preference' => $preference,
            'max_stops' => $maxStops,
            'max_budget' => $maxBudget,
            'departure_window' => $window,
            'mode' => $mode,
            'selected_offer_id' => $data['selected_offer_id'] ?? null,
            'incomplete' => $missing !== [] && $mode !== 'reset',
            'missing' => $missing,
            'source' => 'openai',
        ];
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $overlay
     * @return array<string, mixed>
     */
    private function mergeIntents(array $base, array $overlay, string $source): array
    {
        $merged = [
            'origin' => $overlay['origin'] ?: ($base['origin'] ?? null),
            'destination' => $overlay['destination'] ?: ($base['destination'] ?? null),
            'departure_date' => $overlay['departure_date'] ?: ($base['departure_date'] ?? null),
            'return_date' => $overlay['return_date'] ?? ($base['return_date'] ?? null),
            'adults' => (int) ($overlay['adults'] ?? $base['adults'] ?? 1),
            'cabin' => $overlay['cabin'] ?? ($base['cabin'] ?? 'economy'),
            'preference' => $overlay['preference'] ?? ($base['preference'] ?? 'both'),
            'max_stops' => array_key_exists('max_stops', $overlay) ? $overlay['max_stops'] : ($base['max_stops'] ?? null),
            'max_budget' => array_key_exists('max_budget', $overlay) ? $overlay['max_budget'] : ($base['max_budget'] ?? null),
            'departure_window' => $overlay['departure_window'] ?? ($base['departure_window'] ?? 'any'),
            'mode' => $overlay['mode'] ?? ($base['mode'] ?? 'search'),
            'selected_offer_id' => $overlay['selected_offer_id'] ?? ($base['selected_offer_id'] ?? null),
            'source' => $source,
        ];

        $missing = [];
        if (! $merged['origin']) {
            $missing[] = 'origin';
        }
        if (! $merged['destination']) {
            $missing[] = 'destination';
        }
        if (! $merged['departure_date']) {
            $missing[] = 'departure_date';
        }

        $merged['missing'] = $missing;
        $merged['incomplete'] = $missing !== [] && ($merged['mode'] ?? '') !== 'reset';

        return $merged;
    }

    private function resolvePlace(?string $input): ?string
    {
        $input = trim((string) $input);
        if ($input === '' || Str::lower($input) === 'null') {
            return null;
        }

        $code = $this->duffel->resolveLocation($input);

        return $code !== '' ? $code : null;
    }

    private function extractDate(string $text, string $lower): ?string
    {
        if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $text, $m)) {
            return $this->normalizeDate($m[1]);
        }

        if (preg_match('/\b(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})\b/', $text, $m)) {
            return $this->normalizeDate($m[1]);
        }

        if (preg_match('/\b(\d{1,2})\s+(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)\s*(\d{4})?\b/i', $text, $m)) {
            $year = $m[3] ?? now()->year;
            $candidate = $m[1].' '.$m[2].' '.$year;
            $normalized = $this->normalizeDate($candidate);
            if ($normalized && $normalized < now()->toDateString() && empty($m[3])) {
                $normalized = $this->normalizeDate($m[1].' '.$m[2].' '.(now()->year + 1));
            }

            return $normalized;
        }

        if (preg_match('/\b(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)\s+(\d{1,2})(?:,?\s*(\d{4}))?\b/i', $text, $m)) {
            $year = $m[3] ?? now()->year;
            $candidate = $m[2].' '.$m[1].' '.$year;
            $normalized = $this->normalizeDate($candidate);
            if ($normalized && $normalized < now()->toDateString() && empty($m[3])) {
                $normalized = $this->normalizeDate($m[2].' '.$m[1].' '.(now()->year + 1));
            }

            return $normalized;
        }

        if (str_contains($lower, 'day after tomorrow')) {
            return now()->addDays(2)->toDateString();
        }
        if (str_contains($lower, 'tomorrow')) {
            return now()->addDay()->toDateString();
        }
        if (str_contains($lower, 'today')) {
            return now()->toDateString();
        }

        if (preg_match('/\bnext\s+(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/', $lower, $m)) {
            return Carbon::parse('next '.$m[1])->toDateString();
        }

        return null;
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || Str::lower($value) === 'null') {
            return null;
        }

        try {
            $date = Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($date->lt(now()->startOfDay())) {
            return null;
        }

        return $date->toDateString();
    }

    private function openaiConfigured(): bool
    {
        return filled(config('agent.openai.api_key'));
    }
}
