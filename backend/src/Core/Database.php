<?php
declare(strict_types=1);
namespace App\Core;

use PDO;

class Database {
    private PDO $pdo;

    public function __construct() {
        $defaultDSN = 'sqlite:' . APP_ROOT;
        if ($_ENV['APP_ENV'] === "dev") {
            $defaultDSN .= $_ENV['DB_DSN_DEV'];
        } elseif($_ENV['APP_ENV'] === "prod") {
            $defaultDSN .= $_ENV['DB_DSN_PROD'];
        } else {
             $defaultDSN .= '/database/dev.sqlite';
        }

        $this->pdo = new PDO($defaultDSN);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function getPDO() {
        return $this->pdo;
    }
}
