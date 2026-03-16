<?php
declare(strict_types=1);
namespace App\ORM;

use App\Exception\NotFoundException;

class Appointment extends ORM {
    protected static array $allowedColumns = ['id', 'date', 'time', 'user_id'];
    protected static array $hiddenColumns = [''];
    protected static array $columnTypes = [
        'id' => 'int',
        'date' => 'date',
        'time' => 'time',
        'user_id' => 'int'
    ];
    
    public static function findByDate(string $date): array {
        return static::find(['date' => $date]);
    }

    public function user(): ?User {
        $user = User::findByID($this->user_id);

        if(!$user) {
            throw new NotFoundException('Appointment user not found');
        }

        return $user;
    }

}
