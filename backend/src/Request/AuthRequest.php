<?php
declare(strict_types=1);

namespace App\Request;

use App\Exception\ValidationException;

class AuthRequest {
    public function __construct(private array $data) {}

    public function validateLogin(): void {
        if(empty($this->data['email'])) {
            throw new ValidationException('Email is required');
        }

        if(empty($this->data['password'])) {
            throw new ValidationException('Password is required');
        }

        if(!filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format');
        }
    }

    public function validateRegister(): void {
        $required = ['name', 'surname', 'email', 'password'];
        $missing = [];

        foreach ($required as $field) {
            if(empty($this->data[$field])) {
                $missing[] = $field;
            }
        }

        if(!empty($missing)) {
            throw new ValidationException('Missing required fields: ' . implode(', ', $missing));
        }

        if(!filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format');
        }

        if(\strlen($this->data['password']) < 8) {
            throw new ValidationException('Password must be at least 8 characters long');
        }

    }

    public function getData(): array {
        return $this->data;
    }
}
