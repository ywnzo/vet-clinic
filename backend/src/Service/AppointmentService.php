<?php
declare(strict_types=1);
namespace App\Service;

use App\ORM\Appointment;
use App\Exception\NotFoundException;

class AppointmentService extends BaseService {
    public function index(): array {
        $appointments = Appointment::all();
        return array_map(fn($appointment) => $appointment->toArray(), $appointments);
    }

    public function find(array $args = []): array {
        $appointments = Appointment::find($args);
        return array_map(fn($appointment) => $appointment->toArray(), $appointments);

    }

    public function findByID(int $id): array {
        if (!$id) {
            throw new NotFoundException('Appointment not found');
        }
        $appointment = Appointment::findByID($id);
        if (!$appointment) {
            throw new NotFoundException('Appointment not found');
        }
        return $appointment->toArray();
    }

    public function findByRange(?string $start, ?string $end): array {
        $appointments = Appointment::findByRange($start, $end);
        return array_map(fn($appointment) => $appointment->toArray(), $appointments);
    }

    public function create(array $args): array {
        return Appointment::transaction(function() use ($args) {
            $appointment = new Appointment($args);
            $appointment->save();
            return $appointment->toArray();
        });
    }

    public function update(array $args = []): array {
        $id = (int) ($args['id'] ?? 0);
        if (!$id) {
            throw new NotFoundException('Appointment not found');
        }
        return Appointment::transaction(function() use ($args, $id) {
            $appointment = Appointment::findByID($id);
            if (!$appointment) {
                throw new NotFoundException('Appointment not found');
            }
            foreach ($args as $key => $value) {
                $appointment->$key = $value;
            }
            $appointment->save();
            return $appointment->toArray();
        });
    }

    public function delete(array $args = []): void {
        $id = (int) ($args['id'] ?? 0);
        if (!$id) {
            throw new NotFoundException('Appointment not found');
        }
        Appointment::transaction(function() use ($id) {
            $appointment = Appointment::findByID($id);
            if (!$appointment) {
                throw new NotFoundException('Appointment not found');
            }
            $appointment->delete();
        });
    }
}
