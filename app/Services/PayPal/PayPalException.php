<?php

namespace App\Services\PayPal;

use Exception;

class PayPalException extends Exception
{
    public static function fromResponse(string $message, int $status = 0): self
    {
        return new self($message !== '' ? $message : 'PayPal payment failed.', $status);
    }
}
