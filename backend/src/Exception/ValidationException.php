<?php
declare(strict_types=1);
namespace App\Exception;

class ValidationException extends AppException {
    public function __construct(string $message = 'Validation failed', ?\Throwable $previous = null) {
        parent::__construct($message, $message, 400, $previous);
    }
}
