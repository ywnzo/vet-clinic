<?php
declare(strict_types=1);
namespace App\Policies;

class UserPolicy extends AbstractPolicy {

    public function __construct(array $auth) {
        parent::__construct($auth, 'user');
    }

    public function canIndex(): void {
        if(!$this->isAdmin()) {
            $this->deny('Access denied');
        }
    }

    public function canShow(int $userID): void {
        if(!$this->isAdmin() && $this->getUserId() !== $userID) {
            $this->deny('Access denied');
        }
    }

    public function canSearch(): void {
        if(!$this->isAdmin()) {
            $this->deny('Access denied');
        }
    }

    public function canCreate(): void {
        if(!$this->isAdmin()) {
            $this->deny('Access denied');
        }
    }

    public function canUpdate(int $userID): void {
        if(!$this->isAdmin() && $this->getUserId() !== $userID) {
            $this->deny('Access denied');
        }
    }

    public function canDelete(int $userID): void {
        if(!$this->isAdmin() && $this->getUserId() !== $userID) {
            $this->deny('Access denied');
        }
    }
}
