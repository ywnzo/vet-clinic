<?php
declare(strict_types=1);
namespace App\Exception;

class AppException extends \Exception {
    public function __construct(string $message, private string $userMessage, int $code = 500, ?\Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function getUserMessage(): string {
        return $this->userMessage;
    }

    public function getStatusCode(): int {
        return $this->getCode();
    }
}
