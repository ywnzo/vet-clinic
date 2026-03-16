<?php
declare(strict_types=1);
namespace App\Exception;

class DatabaseException extends AppException {
    public function __construct(string $message = 'Database error', ?\Throwable $previous = null) {
        $userMessage = self::extractUserMessage($message);
        $status = 500;
        $errorCode = null;

        if(str_contains($message, 'UNIQUE constraint failed')) {
            $status = 409;
            $errorCode = 'conflict';
        } elseif(str_contains($message, 'no such table')) {
            $status = 500;
            $errorCode = 'database-missing-table';
        } elseif(str_contains($message, 'NOT NULL constraint failed')) {
            $status = 400;
            $errorCode = 'required-field-missing';
        }
        parent::__construct($message, $userMessage, $status, $errorCode, $previous);
    }

    private static function extractUserMessage(string $message): string {
        if (str_contains($message, 'UNIQUE constraint failed: ')) {
            preg_match('/UNIQUE constraint failed: (.+)/', $message, $matches);
            if(!empty($matches[1])) {
                $field = explode('.', $matches[1])[1];
                return ucfirst($field) . ' already exists';
            }
            return 'This record already exists';
        }

        if(str_contains($message, 'NOT NULL constraint failed: ')) {
            preg_match('/NOT NULL constraint failed: (.+)/', $message, $matches);
            if(!empty($matches[1])) {
                $field = $matches[1];
                return ucfirst($field) . ' is required';
            }
            return 'Required field is missing';
        }

        return 'Database operation failed';
    }
}
