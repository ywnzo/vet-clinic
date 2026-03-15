<?php
declare(strict_types=1);
namespace App\Core;

use Dotenv\Dotenv;

class Config {
    public static function load(): void {
        require __DIR__ . '/../../vendor/autoload.php';

        self::setEnv();
        self::setConsts();
    }

    private static function setEnv(): void {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->load();
    }

    private static function setConsts(): void {
        define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
        define('APP_ROOT', dirname(__DIR__, 2));

        if (APP_ENV === 'production') {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        $logDir = APP_ROOT . '/logs';
        define('LOG_DIR', $logDir);
        if(!is_dir(LOG_DIR)) {
            mkdir(LOG_DIR, 0755, true);
        }
    }
}
