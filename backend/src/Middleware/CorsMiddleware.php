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
        $allowedOrigins = $_ENV['ALLOWED_ORIGINS'] ?? '*';
        $this->allowedOrigins = $allowedOrigins === '*' ? ['*'] : explode(',', $allowedOrigins);
    }

    public function process(Request $request, Handler $handler): Response {
        $origin = $request->getHeaderLine('Origin');
        $allowedOrigin = $this->getAllowOrigin($origin);

        $headers = [
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTION, PATCH',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        ];

        if($allowedOrigin !== '') {
            $headers['Access-Control-Allow-Origin'] = $allowedOrigin;
        }

        $response = $handler->handle($request);
        foreach($headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }
        return $response;
    }

    private function getAllowOrigin(string $origin): string {
        if(\in_array('*', $this->allowedOrigins)) {
            return '*';
        }

        if($origin === '') {
            return '';
        }

        return \in_array($origin, $this->allowedOrigins, true) ? $origin : '';
    }
}
