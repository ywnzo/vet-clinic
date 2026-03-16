<?php
declare(strict_types=1);

namespace App\ORM;

class User extends ORM {
    protected static string $table = 'users';

    protected static array $allowedColumns = ['id', 'name', 'surname', 'email', 'password', 'address', 'role', 'created_at', 'updated_at'];
    protected static array $hiddenColumns = ['password', 'role'];
    protected static array $columnTypes = [
        'id' => 'integer',
        'name' => 'string',
        'surname' => 'string',
        'email' => 'string',
        'password' => 'string',
        'address' => 'string',
        'role' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function findByEmail(string $email): ?static {
        return static::find(['email' => $email])[0] ?? null;
    }

    public static function findByRole(string $role): array {
        return static::find(['role' => $role]);
    }

    public function getFullName(): string {
        return "{$this->name} {$this->surname}";
    }

    public function appointments(): array {
        return $this->hasMany(Appointment::class, 'user_id', 'id');
    }
}
