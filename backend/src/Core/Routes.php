<?php
declare(strict_types=1);
namespace App\Core;


use App\Container;
use Slim\App as Router;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

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
        $this->router->add(function(Request $req, RequestHandler $handler): Response {
            if($req->getMethod() === 'OPTIONS') {
                $response = $handler->handle($req);
                return $response
                    ->withHeader('Access-Control-Allow-Origin', '*')
                    ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                    ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                    ->withHeader('Content-Type', 'application/json');
            }

            $response = $handler->handle($req);
            return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->withHeader('Content-Type', 'application/json');
        });

        $errorMiddleware = $this->router->addErrorMiddleware(APP_ENV === 'development', true, true);
        $defaultHandler = $errorMiddleware->getDefaultErrorHandler();
        $errorMiddleware->setDefaultErrorHandler(function (Request $req, \Throwable $e, bool $displayError, bool $logErrors) use ($defaultHandler) {
            $response = $defaultHandler($req, $e, $displayError, $logErrors);
            return $response
                ->withHeader('Content-Type', 'application/json');
        });

    }

    private function registerRoutes() {
        $userController = $this->container->get('userController');
        $this->router->get('/api/users', [$userController, 'index']);
        $this->router->get('/api/users/{id}', [$userController, 'find']);
        $this->router->post('/api/users/search', [$userController, 'find']);
        $this->router->post('/api/users', [$userController, 'create']);
        $this->router->put('/api/users/{id}', [$userController, 'update']);
        $this->router->delete('/api/users/{id}', [$userController, 'delete']);

        $this->router->get('/api/health', function(Request $req, Response $res): Response {
            $res->getBody()->write(
                json_encode(['status' => 'ok', 'timestamp' => date('Y-m-d H:i:s')])
            );
            return $res->withStatus(200);
        });
    }


}
