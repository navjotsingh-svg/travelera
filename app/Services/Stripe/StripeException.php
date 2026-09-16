<?php

namespace App\Services\Stripe;

use Exception;
use Stripe\Exception\ApiErrorException;

class StripeException extends Exception
{
    public static function fromStripe(ApiErrorException $exception): self
    {
        $message = $exception->getMessage() ?: 'Stripe payment failed.';

        return new self($message, (int) $exception->getCode(), $exception);
    }
}
