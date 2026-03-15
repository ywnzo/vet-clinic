<?php
namespace App;

use App\Core\Database;
use App\Core\Logger;

use App\Service\UserService;
use App\Service\AuthService;
use App\Service\PolicyService;

use App\Controller\UserController;
use App\Controller\AuthController;
use App\Middleware\AuthMiddleware;

use App\Policies\UserPolicy;

class Container {
    private array $bindings = [];
    private array $singletonFlags = [];
    private array $instances = [];

    public function __construct() {
        $this->registerDefaults();
    }

    private function registerDefaults(): void {
        $this->singleton('database', fn(Container $c) => new Database());
        $this->singleton('logger', fn(Container $c) => new Logger());

        $this->singleton('authService', fn(Container $c) => new AuthService());
        $this->singleton('userService', fn(Container $c) => new UserService());

        $this->singleton('policyService', function (Container $c) {
            $policyService = new PolicyService();
            $policyService->register('user', UserPolicy::class);
            return $policyService;
        });

        $this->singleton('userController', fn(Container $c) => new UserController($c->get('userService'), $c->get('logger')));
        $this->singleton('authController', fn(Container $c) => new AuthController($c->get('authService'), $c->get('logger')));

        $this->singleton('authMiddleware', fn(Container $c) => new AuthMiddleware($c->get('authService'), $c->get('policyService')));
    }

    private function singleton(string $name, callable $callback): void {
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
