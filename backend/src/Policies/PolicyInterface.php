<?php
declare(strict_types=1);
namespace App\Policies;

interface PolicyInterface {
    public function authorize(string $action, ...$params): void;
}
