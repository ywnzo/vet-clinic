<?php
declare(strict_types=1);
namespace App\Service;

use App\ORM\User;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;


class UserService extends BaseService {
    public function index(): array {
        $users = User::all();
        return array_map(fn($user) => $user->toArray(), $users);
    }

    public function find(array $args = []): array {
        $users = User::find(
            $args['filters'] ?? [],
            $args['sort'] ?? [],
            (int) ($args['page'] ?? 1),
            (int) ($args['offset'] ?? 0)
        );
        return array_map(fn($user) => $user->toArray(), $users);
    }

    public function findByID(int $id): array {
        if (!$id) {
            throw new ValidationException('ID is required');
        }
        $user = User::findById($id);
        if($user === null) {
            throw new NotFoundException('User not found');
        }
        return $user->toArray();
    }

    public function create(array $args): array {
        $user = User::transaction(function() use ($args) {
            $user = new User($args);
            $user->save();
            return $user;
        });
        return $user->toArray();
    }

    public function update(array $args = []): array {
        $id = (int) ($args['id'] ?? 0);
        if (!$id) {
            throw new ValidationException('ID is required');
        }
        $user = User::findByID($id);
        if (!$user) {
            throw new NotFoundException('User not found');
        }
        $body = $args['body'] ?? [];
        User::transaction(function() use ($user, $body) {
            foreach($body as $key => $value) {
                $user->$key = $value;
            }
            $user->save();
        });
        return $user->toArray();
    }

    public function delete(array $args = []): void {
        $id = (int) ($args['id'] ?? 0);
        $user = User::findByID($id);
        if (!$user) {
            throw new NotFoundException('User not found');
        }

        User::transaction(function() use ($user) {
            $user->delete();
        });
    }
}
