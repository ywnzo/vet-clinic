<?php
namespace App\Validation\Rules;

use App\Validation\Rule;

class EmailRule implements Rule {
    public function validate(mixed $value): bool {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function getMessage(): string {
        return 'Invalid email format';
    }
}
