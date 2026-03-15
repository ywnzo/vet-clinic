<?php
declare(strict_types=1);
namespace App;

use App\Container;
use App\Core\Routes;
use App\ORM\ORM;

use Slim\Factory\AppFactory;
use Slim\App as SlimApp;

class App {
    private SlimApp $slim;
    private Container $container;

    public function __construct() {
        $this->container = new Container();
        ORM::setPDO($this->container->get('database')->getPDO());

        $this->slim = AppFactory::create();
        $routes = new Routes($this->container);
        $routes->register($this->slim);

    }

    public function run() {
        try {
            $this->slim->run();
        } catch (\Throwable $e) {
            $this->container->get('logger')->error('Application error: ' . $e->getMessage(), [
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            http_response_code(500);
            echo json_encode([
                'error' => 'Internal server error',
            ]);
        }
    }

}
