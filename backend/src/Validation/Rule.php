<?php
namespace App\Validation;

interface Rule {
    public function validate(mixed $value): bool;
    public function getMessage(): string;
}
