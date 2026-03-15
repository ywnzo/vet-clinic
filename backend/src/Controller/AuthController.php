<?php
declare(strict_types=1);
namespace App\Controller;

use App\Core\Logger;
use App\Exception\ValidationException;
use App\Service\AuthService;
use App\Request\AuthRequest;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController extends BaseController {
    public function __construct(private AuthService $service, Logger $logger) {
        parent::__construct($logger);
    }

    public function register(Request $req, Response $res): Response {
        return $this->respond($res, function ($res) use ($req) {
            $body = $req->getParsedBody() ?? [];
            $authRequest = new AuthRequest($body);
            $authRequest->validateRegister();

            $result = $this->service->register($body);
            return $this->success($res, $result, 201);
        });
    }

    public function login(Request $req, Response $res): Response {
        return $this->respond($res, function ($res) use ($req) {
            $body = $req->getParsedBody() ?? [];
            $authRequest = new AuthRequest($body);
            $authRequest->validateLogin();

            $result = $this->service->login($body);
            return $this->success($res, $result);
        });
    }

    public function refresh(Request $req, Response $res): Response {
        return $this->respond($res, function ($res) use ($req) {
            $body = $req->getParsedBody() ?? [];
            $refreshToken = $body['refresh_token'] ?? null;

            if(empty($refreshToken)) {
                throw new ValidationException("Refresh token is required");
            }

            $result = $this->service->refresh(['refresh_token' => $refreshToken]);
            return $this->success($res, $result);
        });
    }

    public function logout(Request $req, Response $res): Response {
        return $this->respond($res, function ($res) use ($req) {
            $body = $req->getParsedBody() ?? [];
            $refreshToken = $body['refresh_token'] ?? null;

            if(empty($refreshToken)) {
                throw new ValidationException("Refresh token is required");
            }

            $this->service->logout($refreshToken);
            return $this->noContent($res);
        });
    }

}
