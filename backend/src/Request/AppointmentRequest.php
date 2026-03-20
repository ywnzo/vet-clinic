<?php
declare(strict_types=1);
namespace App\Request;

use App\Validation\Validator;
use App\Validation\Rules\{RequiredRule, EmailRule, StringLengthRule};

class AppointmentRequest {
    private array $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function getData(): array {
        return $this->data;
    }

    public function validateCreate(): void {
        $validator = new Validator();
        $validator
            ->addRules('user_id', new RequiredRule())
            ->addRules('date', new RequiredRule())
            ->addRules('time', new RequiredRule());
        $validator->validate($this->data);
    }

    public function validateUpdate(): void {
        $validator = new Validator();
        if(isset($this->data['user_id'])) {
            $validator->addRules('user_id', new RequiredRule());
        }
        if(isset($this->data['date'])) {
            $validator->addRules('date', new RequiredRule());
        }
        if(isset($this->data['time'])) {
            $validator->addRules('time', new RequiredRule());
        }
        $validator->validate($this->data);
    }
}
