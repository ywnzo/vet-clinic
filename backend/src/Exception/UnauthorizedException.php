<?php
declare(strict_types=1);
namespace App\Exception;

class UnauthorizedException extends AppException {
    public function __construct(string $message = 'Unauthorized', ?\Throwable $previous = null) {
        parent::__construct($message, $message, 401, $previous);
    }
}
