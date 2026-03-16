<?php
declare(strict_types=1);
namespace App\Middleware;

use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

class RequestLoggingMiddleware implements MiddlewareInterface {
    private LoggerInterface $logger;
    private string $requestIdHeader;

    public function __construct(LoggerInterface $logger, string $requestIdHeader) {
        $this->logger = $logger;
        $this->requestIdHeader = $requestIdHeader;
    }

    public function process(Request $request, Handler $handler): Response {
        $start = microtime(true);
        $requestId = $request->getHeaderLine($this->requestIdHeader);
        if(empty($requestId)) {
            $requestId = bin2hex(random_bytes(12));
            $request = $request->withHeader($this->requestIdHeader, $requestId);
        }

        $request = $request->withAttribute('requestId', $requestId);

        $user = $request->getAttribute('user');
        $userId = null;
        if(\is_object($user) && property_exists($user, 'id')) {
            $userId = $user->id;
        } elseif(\is_array($user) && isset($user['id'])) {
            $userId = $user['id'];
        }

        $serverParams = $request->getServerParams();
        $ip = $serverParams['REMOTE_ADDR'] ?? ($request->getHeaderLine('X-Forwarded-For') ?: 'unknown');
        $method = $request->getMethod();
        $uri = (string)$request->getUri()->getPath();
        $query = $request->getQueryParams();

        $this->logger->info("request.start", [
            'requestId' => $requestId,
            'method' => $method,
            'path' => $uri,
            'query' => $query,
            'ip' => $ip,
            'userId' => $userId,
        ]);

        $response = $handler->handle($request);

        $end = microtime(true);
        $duration = (int) (($end - $start) * 1000);
        $statusCode = $response->getStatusCode();

        $this->logger->info("request.end", [
            'requestId' => $requestId,
            'method' => $method,
            'path' => $uri,
            'statusCode' => $statusCode,
            'duration' => $duration,
            'ip' => $ip,
            'userId' => $userId,
        ]);

        return $response->withHeader($this->requestIdHeader, $requestId);
    }
}
