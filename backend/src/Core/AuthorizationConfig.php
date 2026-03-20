<?php
declare(strict_types=1);
namespace App\Core;

class Entity {
    public function __construct(
        public string $resource,
        public string $pathPrefix,
        public $entityClass,
        public string $ownerField = 'id'
    ) {}

    public function getResource(): string { return $this->resource; }
    public function getEntityClass(): string { return $this->entityClass; }
    public function getOwnerField(): string { return $this->ownerField; }
    public function getPathPrefix(): string { return $this->pathPrefix; }

}

class AuthorizationConfig {
    public static function getConfig(): array {
        return [
            'user' => new Entity('user', '/api/users', \App\ORM\User::class),
            'appointment' => new Entity('appointment', '/api/appointments', \App\ORM\Appointment::class, 'user_id'),
        ];
    }

    public static function fromPath(string $path): ?Entity {
        foreach(self::getConfig() as $config) {
            if (strpos($path, $config->pathPrefix)) {
                return $config;
            }
        }
        return null;
    }
}
