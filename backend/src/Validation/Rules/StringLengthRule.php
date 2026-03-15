<?php
namespace App\Validation\Rules;

use App\Validation\Rule;

class StringLengthRule implements Rule {
    public function __construct(private int $min = 0, private ?int $max = null) {}

    public function validate(mixed $value): bool {
        if(!\is_string($value)) {
            return false;
        }

        $length = \strlen($value);
        if ($length < $this->min) {
            return false;
        }
        return $this->max === null || $length <= $this->max;
    }

    public function getMessage(): string {
        if ($this->max === null) {
            return "Must be at least {$this->min} characters";
        }
        return "Must be between {$this->min} and {$this->max} characters";
    }
}
