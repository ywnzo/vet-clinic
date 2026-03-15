<?php
declare(strict_types=1);
namespace App\Controller;

use App\Core\Logger;
use App\Request\UserRequest;
use App\Service\UserService;

use Psr\Http\Message\ServerRequestInterface  as Request;
use Psr\Http\Message\ResponseInterface as Response;

class UserController extends BaseController {
    protected UserService $service;

    public function __construct(UserService $service, Logger $logger) {
        parent::__construct($logger);
        $this->service = $service;
    }

    public function index(Request $req, Response $res): Response {
        return $this->respond($res, function($res) {
            $users = $this->service->index();
            return $this->success($res, $users);
        });
    }

    public function find(Request $req, Response $res, array $args = []): Response {
        return $this->respond($res, function($res) use ($req, $args) {
            if (isset($args['id'])) {
                $user = $this->service->findByID((int) $args['id']);
                return $this->success($res, $user);
            }

            $body = $req->getParsedBody() ?? [];
            $users = $this->service->find($body);
            return $this->success($res, $users);
        });
    }

    public function create(Request $req, Response $res): Response {
        return $this->respond($res, function($res) use ($req) {
            $body = $req->getParsedBody() ?? [];

            $userRequest = new UserRequest($body);
            $userRequest->validateCreate();

            $user = $this->service->create($body);
            return $this->success($res, $user, 201);
        });
    }

    public function update(Request $req, Response $res, array $args = []): Response {
        return $this->respond($res, function($res) use ($req, $args) {
            $body = $req->getParsedBody() ?? [];

            $userRequest = new UserRequest($body);
            $userRequest->validateUpdate();

            $id = $args['id'] ?? 0;
            $user = $this->service->update(['id' => $id, 'body' => $body]);
            return $this->success($res, $user);
        });
    }

    public function delete(Request $req, Response $res, array $args = []): Response {
        return $this->respond($res, function($res) use ($args) {
            $id = $args['id'] ?? 0;
            $this->service->delete(['id' => $id]);
            return $this->noContent($res);
        });
    }

}
