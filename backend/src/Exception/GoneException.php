<?php
declare(strict_types=1);
namespace App\Exception;

class GoneException extends AppException {
    public function __construct(string $message = 'Gone', string $userMessage = 'Gone', ?string $errorCode = null, ?\Throwable $previous = null) {
        parent::__construct($message, $userMessage, 410, $errorCode, $previous);
    }
}
