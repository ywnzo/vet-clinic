<?php
declare(strict_types=1);
namespace App\Middleware;

use App\Response\ApiResponse;
use App\Service\AuthService;
use App\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface {
    public function __construct(private AuthService $authService) {}

    public function process(Request $request, Handler $handler): Response {
        try {
            $authHandler = $request->getHeaderLine('Authorization');
            if (empty($authHandler) || !str_starts_with($authHandler, 'Bearer ')) {
                throw new UnauthorizedException('Missing or invalid Authorization header');
            }

            $token = substr($authHandler, 7);
            $payload = $this->authService->validateAccessToken($token);
            $request = $request->withAttribute('auth', $payload);
            return $handler->handle($request);
        } catch (UnauthorizedException $e) {
            $response = new SlimResponse();
            return ApiResponse::error($response, $e->getUserMessage(), $e->getStatusCode());
        }
    }
}
