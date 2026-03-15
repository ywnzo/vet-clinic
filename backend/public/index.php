<?php
declare(strict_types=1);

require __DIR__ . "/../vendor/autoload.php";

use App\App;
use App\Core\Config;

Config::load();

$app = new App();
$app->run();
