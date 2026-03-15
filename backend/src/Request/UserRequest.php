<?php
declare(strict_types=1);
namespace App\Request;

use App\Validation\Validator;
use App\Validation\Rule\{RequiredRule, EmailRule, StringRule};

class UserRequest {
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
            ->addRules('name', new RequiredRule(), new StringRule(1, 64))
            ->addRules('surname', new RequiredRule(), new StringRule(1, 64))
            ->addRules('email', new RequiredRule(), new EmailRule())
            ->addRules('password', new RequiredRule(), new StringRule(1));
        $validator->validate($this->data);
    }

    public function validateUpdate(): void {
        $validator = new Validator();
        if(isset($this->data['email'])) {
            $validator->addRules('email', new EmailRule());
        }

        if(isset($this->data['name'])) {
            $validator->addRules('name', new RequiredRule(), new StringRule(1, 64));
        }

        if(isset($this->data['surname'])) {
            $validator->addRules('surname', new RequiredRule(), new StringRule(1, 64));
        }

        if(isset($this->data['address'])) {
            $validator->addRules('address', new RequiredRule(), new StringRule(1, 128));
        }
        $validator->validate($this->data);
    }

}
