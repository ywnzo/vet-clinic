<?php
declare(strict_types=1);
namespace App\Request;

use App\Exception\ValidationException;

class UserRequest {
    private array $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function getData(): array {
        return $this->data;
    }

    public function validateCreate(): void {
        $required = ['name', 'surname', 'email', 'password'];
        $missing = [];

        foreach ($required as $field) {
            if (empty($this->data[$field])) {
                $missing[] = $field;
            }
        }

        if(!empty($missing)) {
            throw new ValidationException("Missing required fields: " . implode(', ', $missing));
        }

        if(!filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException("Invalid email format");
        }

        $this->validateStringField('name', 1, 255);
        $this->validateStringField('surname', 1, 255);
        $this->validateStringField('address', 1, 500);
    }

    public function validateUpdate(): void {
        if(isset($this->data['email'])) {
            if(!filter_var($this->data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException("Invalid email format");
            }
        }

        if(isset($this->data['name'])) {
            $this->validateStringField('name', 1, 64);
        }

        if(isset($this->data['surname'])) {
            $this->validateStringField('surname', 1, 64);
        }

        if(isset($this->data['address'])) {
            $this->validateStringField('address', 1, 128);
        }
    }

    private function validateStringField(string $field, int $min, int|null $max = null): void {
        if(!\is_string($this->data[$field])) {
            throw new ValidationException("Invalid value for $field: must be a string");
        }

        if(\strlen($this->data[$field]) < $min) {
            throw new ValidationException("Invalid value for $field: must be at least $min characters long");
        }

        if($max !== null && \strlen($this->data[$field]) > $max) {
            throw new ValidationException("Invalid value for $field: must be at most $max characters long");
        }
    }

}
