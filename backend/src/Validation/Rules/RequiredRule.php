<?php
namespace App\Validation\Rules;

use App\Validation\Rule;

class RequiredRule implements Rule {
    public function validate(mixed $value): bool {
        return !empty($value);
    }

    public function getMessage(): string {
        return 'This field is required';
    }
}
