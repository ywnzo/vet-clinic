<?php
declare(strict_types=1);
namespace App\Policies;

class AppointmentPolicy extends AbstractPolicy {

    public function __construct(array $auth) {
        parent::__construct($auth, 'appointment');
    }

    public function canIndex(): void {
        if (!$this->isAdmin()) {
            $this->deny();
        }
    }

    public function canShow(int $apptUserId): void {
        if (!$this->isAdmin() && $this->getUserId() !== $apptUserId) {
            $this->deny();
        }
    }

    public function canSearch(): void {
        if (!$this->isAdmin()) {
            $this->deny();
        }
    }

    public function canCreate(): void {
        if (!$this->isAdmin()) {
            $this->deny();
        }
    }

    public function canUpdate(int $apptUserId): void {
        if (!$this->isAdmin() && $this->getUserId() !== $apptUserId) {
            $this->deny();
        }
    }

    public function canDelete(int $apptUserId): void {
        if (!$this->isAdmin() && $this->getUserId() !== $apptUserId) {
            $this->deny();
        }
    }
}
