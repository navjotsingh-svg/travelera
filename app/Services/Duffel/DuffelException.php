<?php

namespace App\Services\Duffel;

use Exception;
use Illuminate\Http\Client\Response;

class DuffelException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly array $errors = [],
        ?Exception $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }

    public static function fromResponse(Response $response): self
    {
        $payload = $response->json();
        $errors = is_array($payload['errors'] ?? null) ? $payload['errors'] : [];
        $first = $errors[0] ?? [];
        $message = $first['message'] ?? $first['title'] ?? 'Duffel request failed (HTTP '.$response->status().').';

        return new self($message, $response->status(), $errors);
    }
}
