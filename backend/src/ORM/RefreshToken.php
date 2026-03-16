<?php
declare(strict_types=1);
namespace App\ORM;

class RefreshToken extends ORM {
    protected static string $table = 'refresh_tokens';

    protected static array $allowedColumns = ['id', 'user_id', 'token', 'expires_at', 'created_at'];
    protected static array $columnTypes = [
        'id' => 'integer',
        'user_id' => 'integer',
        'token' => 'string',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];


    public function getUser(): ?User {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

}
