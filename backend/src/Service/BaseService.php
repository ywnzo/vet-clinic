<?php
declare(strict_types=1);
namespace App\Service;

abstract class BaseService {
    public function __construct() {}

    abstract protected function index(): array;
    abstract protected function find(array $args = []): array;
    abstract protected function create(array $args): array;
    abstract protected function update(array $args = []): array;
    abstract protected function delete(array $args = []): void;
}
