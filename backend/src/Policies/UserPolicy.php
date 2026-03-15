<?php
declare(strict_types=1);
namespace App\Policies;

use App\Exception\UnauthorizedException;

class UserPolicy {
    private array $auth;

    public function __construct(array $auth) {
        $this->auth = $auth;
    }

    public function canIndex(): void {
        if ($this->getRole() !== 'admin') {
            throw new UnauthorizedException('Access denied', 403);
        }
    }

    public function canShow(int $userID): void {
        $isAdmin = $this->getRole() === 'admin';
        $isOwner = $this->auth['sub'] === $userID;
        if (!$isAdmin && !$isOwner) {
            throw new UnauthorizedException('Access denied', 403);
        }
    }

    public function canSearch(): void {
        $isAdmin = $this->getRole() === 'admin';

        if (!$isAdmin) {
            throw new UnauthorizedException('Access denied', 403);
        }
    }

    public function canCreate(): void {
        if ($this->getRole() !== 'admin') {
            throw new UnauthorizedException('Access denied', 403);
        }
    }

    public function canUpdate(int $userID): void {
        $isAdmin = $this->getRole() === 'admin';
        $isOwner = $this->auth['sub'] === $userID;
        if (!$isAdmin && !$isOwner) {
            throw new UnauthorizedException('Access denied', 403);
        }
    }

    public function canDelete(int $userID): void {
        $isAdmin = $this->getRole() === 'admin';
        $isOwner = $this->auth['sub'] === $userID;

        if (!$isAdmin && !$isOwner) {
            throw new UnauthorizedException('Access denied', 403);
        }
    }

    private function getRole(): string {
        return $this->auth['role'] ?? 'user';
    }
}
