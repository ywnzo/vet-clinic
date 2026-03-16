<?php
declare(strict_types=1);
namespace App\Core;


use App\Container;
use App\Middleware\RequestLoggingMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\ErrorMiddleware;

use Slim\App as Router;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Routes {
    private Router $router;

    public function __construct(private Container $container) {}

    public function register(Router $router): void {
        $this->router = $router;
        $this->setupMiddleware();
        $this->registerRoutes();
    }

    private function setupMiddleware() {
        $this->router->addBodyParsingMiddleware();
        $this->router->add(new RequestLoggingMiddleware(
            $this->container->get('logger'),
            $_ENV['REQUEST_ID_HEADER'] ?? 'X-Request-Id'
        ));
        $this->router->add(new CorsMiddleware());
        ErrorMiddleware::register($this->router);
    }

    private function registerRoutes() {
        $authController = $this->container->get('authController');
        $userController = $this->container->get('userController');
        $authMiddleware = $this->container->get('authMiddleware');

        $this->router->group('/api/auth', function(RouteCollectorProxy $group) use ($authController) {
            $group->post('/register', [$authController, 'register']);
            $group->post('/login', [$authController, 'login']);
            $group->post('/refresh', [$authController, 'refresh']);
            $group->post('/logout', [$authController, 'logout']);
        });

        $this->router->group('/api/users', function(RouteCollectorProxy $group) use ($userController, $authMiddleware) {
            $group->get('', [$userController, 'index']);
            $group->get('/{id}', [$userController, 'find']);
            $group->post('/search', [$userController, 'find']);
            $group->post('', [$userController, 'create']);
            $group->put('/{id}', [$userController, 'update']);
            $group->delete('/{id}', [$userController, 'delete']);
        })->add($authMiddleware);

        $this->router->get('/api/health', function(Request $req, Response $res): Response {
            $res->getBody()->write(
                json_encode(['status' => 'ok', 'timestamp' => date('Y-m-d H:i:s')])
            );
            return $res->withStatus(200);
        });
    }


}
