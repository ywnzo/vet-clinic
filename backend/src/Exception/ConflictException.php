<?php
declare(strict_types=1);
namespace App\Exception;

class ConflictException extends AppException {
    public function __construct(string $message = 'Conflict', string $userMessage = 'Conflict', ?string $errorCode = null, ?\Throwable $previous = null) {
        parent::__construct($message, $userMessage, 409, $errorCode, $previous);
    }
}
