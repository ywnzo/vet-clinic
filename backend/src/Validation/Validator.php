<?php
declare(strict_types=1);
namespace App\Validation;

use App\Exception\ValidationException;

class Validator {
    private array $rules = [];
    private array $fieldLabels = [];

    public function addRule(string $field, Rule $rule): self {
        if(!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }
        $this->rules[$field][] = $rule;
        return $this;
    }

    public function addRules(string $field, ...$rules): self {
        foreach($rules as $rule) {
            $this->addRule($field, $rule);
        }
        return $this;
    }

    public function setFieldLabel(string $field, string $label): self {
        $this->fieldLabels[$field] = $label;
        return $this;
    }

    public function validate(array $data): bool {
        $errors = [];

        foreach($this->rules as $field => $rules) {
            $value = $data[$field] ?? null;
            foreach($rules as $rule) {
                if(!$rule->validate($value)) {
                    $errors[$field] = $rule->getMessage();
                    break;
                }
            }
        }

        if(!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        return true;
    }

    public function isValid(string $field, mixed $value): bool {
        if(!isset($this->rules[$field])) {
            return true;
        }

        foreach($this->rules[$field] as $rule) {
            if(!$rule->validate($value)) {
                return false;
            }
        }

        return true;
    }
}
