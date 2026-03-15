<?php
declare(strict_types=1);

namespace App\ORM;

class User extends ORM {
    protected static array $allowedColumns = ['id', 'name', 'surname', 'email', 'password', 'address', 'created_at', 'updated_at'];
    protected static array $hiddenColumns = ['password'];
    protected static array $columnTypes = [
        'id' => 'integer',
        'name' => 'string',
        'surname' => 'string',
        'email' => 'string',
        'password' => 'string',
        'address' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
