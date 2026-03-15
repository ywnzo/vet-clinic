<?php
declare(strict_types=1);

namespace App\Policies;

use App\Exception\UnauthorizedException;

abstract class AbstractPolicy implements PolicyInterface {
    protected array $auth;
    protected string $resourceName;

    public function __construct(array $auth, string $resourceName) {
        $this->auth = $auth;
        $this->resourceName = $resourceName;
    }

    public function authorize(string $action, ...$params): void {
        $method = 'can' . ucfirst($action);
        if (!method_exists($this, $method)) {
            throw new UnauthorizedException("Action: {$action} not defined for resource: {$this->resourceName}");
        }
        $this->$method(...$params);
    }

    protected function getRole(): string {
        return $this->auth['role'] ?? 'user';
    }

    protected function getUserId(): int {
        return $this->auth['sub'] ?? 0;
    }

    protected function isAdmin(): bool {
        return $this->getRole() === 'admin';
    }

    protected function isOwner(int $resourceOwnerId): bool {
        return  $this->getUserId() === $resourceOwnerId;
    }

    protected function deny(string $reason = "Access denied"): void {
        throw new UnauthorizedException($reason, 403);
    }
}
