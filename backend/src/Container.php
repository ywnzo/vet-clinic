<?php
namespace App;

use App\Core\Database;
use App\Core\Logger;
use App\Controller\UserController;
use App\Service\UserService;

class Container {
    private array $bindings = [];
    private array $singletonFlags = [];
    private array $instances = [];

    public function __construct() {
        $this->registerDefaults();
    }

    private function registerDefaults(): void {
        $this->signleton('database', fn(Container $container) => new Database());
        $this->signleton('logger', fn(Container $container) => new Logger());
        $this->signleton('userService', fn(Container $container) => new UserService($container->get('database')));
        $this->signleton('userController', fn(Container $container) => new UserController($container->get('userService'), $container->get('logger')));
    }

    private function signleton(string $name, callable $callback): void {
        $this->bindings[$name] = $callback;
        $this->singletonFlags[$name] = true;
    }

    public function bind(string $name, callable $callback): void {
        $this->bindings[$name] = $callback;
        $this->singletonFlags[$name] = false;
    }

    public function get(string $name): mixed {
        if (!isset($this->bindings[$name])) {
            throw new \Exception("Binding not found: $name");
        }


        if ($this->singletonFlags[$name] && isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        $instance = $this->bindings[$name]($this);
        if ($this->singletonFlags[$name]) {
            $this->instances[$name] = $instance;
        }

        return $instance;
    }

    public function has(string $name): bool {
        return isset($this->bindings[$name]);
    }
}
