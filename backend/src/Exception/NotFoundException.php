<?php
declare(strict_types=1);
namespace App\Exception;

class NotFoundException extends AppException {
    public function __construct(string $message = "Not Found", string $userMessage = "Resource not found", ?string $errorCode = null, ?\Throwable $previous = null) {
        parent::__construct($message, $userMessage ?? $message, 404, $errorCode ?? 'not_found', $previous);
    }

}
