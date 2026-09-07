<?php

namespace App\Services\Sales;

use Exception;

class CommercialValidationException extends Exception
{
    public function __construct(
        string $message = "Falha na validação comercial antes do fechamento.",
        public readonly array $validationErrors = [],
        int $code = 422,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
