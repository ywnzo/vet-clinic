<?php
declare(strict_types=1);
namespace App\Exception;

class AppException extends \Exception {
    public function __construct(string $message, private string $userMessage, int $code = 500, private ?string $errorCode = null, ?\Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function getUserMessage(): string {
        return $this->userMessage;
    }

    public function getStatusCode(): int {
        return $this->getCode();
    }

    public function getErrorCode(): ?string {
        if($this->errorCode !== null) {
            return $this->errorCode;
        }

        $class = (new \ReflectionClass($this))->getShortName();
        $class = preg_replace('/Exception$/', '', $class);
        $snake = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $class));
        return $snake ?: 'error';
    }
}
