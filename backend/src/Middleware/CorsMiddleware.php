<?php
declare(strict_types=1);
namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class CorsMiddleware implements MiddlewareInterface {
    private array $allowedOrigins;
    public function __construct() {
        $this->allowedOrigins = explode(',', $_ENV['ALLOWED_ORIGINS'] ?? '*');
    }

    public function process(Request $request, Handler $handler): Response {
        $origin = $request->getHeaderLine('Origin');
        $allowedOrigin = \in_array('*', $this->allowedOrigins) ? '*' : (\in_array($origin, $this->allowedOrigins) ? $origin : '');

        $headers = [
            'Access-Control-Allow-Origin' => $allowedOrigin,
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTION, PATCH',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        ];

        if($request->getMethod() === 'OPTIONS') {
            $response = $handler->handle($request);
            foreach($headers as $key => $value) {
                $response = $response->withHeader($key, $value);
            }
            return $response;
        }

        $response = $handler->handle($request);
        foreach($headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }
        return $response;
    }
}
