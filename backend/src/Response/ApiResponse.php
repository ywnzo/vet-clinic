<?php
declare(strict_types=1);
namespace App\Response;

use Psr\Http\Message\ResponseInterface as Response;

class ApiResponse {
    public static function success(Response $res, mixed $data, int $statusCode = 200): Response {
        $payload = [
            'status' => 'success',
            'data' => $data,
        ];

        $res->getBody()->write(json_encode($payload));
        return $res->withStatus($statusCode)->withHeader('Content-Type', 'application/json');
    }

    public static function paginated(Response $res, array $data, int $count, int $page, int $perPage = 10, int $statusCode = 200): Response {
        $payload = [
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'count' => $count,
                'page' => $page,
                'perPage' => $perPage,
                'total' => \count($data)
            ],
        ];

        $res->getBody()->write(json_encode($payload));
        return $res->withStatus($statusCode)->withHeader('Content-Type', 'application/json');
    }

    public static function error(Response $res, string $message, int $statusCode = 400, array $debug = []): Response {
        $payload = [
            'status' => 'error',
            'message' => $message,
            'code' => $statusCode,
        ];

        if(!empty($debug)) {
            $payload['debug'] = $debug;
        }

        $res->getBody()->write(json_encode($payload));
        return $res->withStatus($statusCode)->withHeader('Content-Type', 'application/json');
    }

    public static function noContent(Response $res): Response {
        return $res->withStatus(204);
    }


}
