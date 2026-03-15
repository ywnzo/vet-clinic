<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Response\ApiResponse;
use App\Service\AuthService;
use App\Service\PolicyService;
use App\Exception\UnauthorizedException;
use App\ORM\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface {
    public function __construct(private AuthService $authService, private PolicyService $policyService) {}

    public function process(Request $request, Handler $handler): Response {
        try {
            $authHandler = $request->getHeaderLine('Authorization');
            if (empty($authHandler) || !str_starts_with($authHandler, 'Bearer ')) {
                throw new UnauthorizedException('Missing or invalid Authorization header');
            }

            $token = substr($authHandler, 7);
            $payload = $this->authService->validateAccessToken($token);

            $user = User::findByID((int)$payload['sub']);
            if($user == null) {
                throw new UnauthorizedException('User not found', 401);
            }

            if($user->role !== $payload['role']) {
                throw new UnauthorizedException('User role has been changed', 401);
            }

            $request = $request->withAttribute('auth', (array)$payload);
            $request = $request->withAttribute('user', $user);

            $this->authorizeUserRoutes($request, $payload);

            return $handler->handle($request);
        } catch (UnauthorizedException $e) {
            $response = new SlimResponse();
            return ApiResponse::error($response, $e->getUserMessage(), $e->getStatusCode());
        }
    }

    public function authorizeUserRoutes(Request $request, array $payload): void {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();

        if (!str_starts_with($path, '/api/users')) {
            return;
        }

        $policy = $this->policyService->getPolicy('user', $payload);

        $authParts = explode('/', $path);
        $userID = (int)end($authParts);

        $action = match ($method) {
            'GET' => $userID ? 'show' : 'index',
            'POST' => str_contains($path, '/search') ? 'search' : 'create',
            'PUT' => 'update',
            'DELETE' => 'delete',
            default => 'index',
        };

        if(($method === 'GET' || $method === 'PUT' || $method === 'DELETE') && $userID) {
            $policy->authorize($action, $userID);
        } else {
            $policy->authorize($action);
        }
    }
}
