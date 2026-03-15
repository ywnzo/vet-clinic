<?php
declare(strict_types=1);
namespace App\Exception;

class ValidationException extends AppException {
    public array $errors;

    public function __construct(string $message = 'Validation failed', array $errors = [], ?\Throwable $previous = null) {
        parent::__construct($message, $message, 400, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array {
        return $this->errors;
    }

}
