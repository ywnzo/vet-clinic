<?php
declare(strict_types=1);
namespace App\Controller;

use App\Core\Logger;
use App\Request\AppointmentRequest;
use App\Service\AppointmentService;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AppointmentController extends BaseController {
    protected AppointmentService $service;

    public function __construct(AppointmentService $service, Logger $logger) {
        parent::__construct($logger);
        $this->service = $service;
    }

    public function index(Request $req, Response $res): Response {
        return $this->respond($res, function() use ($res) {
            $appointments = $this->service->index();
            return $this->paginated($res, $appointments, \count($appointments));
        });
    }

    public function find(Request $req, Response $res, array $args): Response {
        return $this->respond($res, function() use ($res, $req, $args) {
            if(isset($args['id'])) {
                $appointment = $this->service->findByID((int)$args['id']);
                return $this->success($res, $appointment);
            }

            $body = $req->getParsedBody() ?? [];
            $appointments = $this->service->find($body);
            return $this->success($res, $appointments);
        });
    }

    public function create(Request $req, Response $res, array $args): Response {
        return $this->respond($res, function() use ($res, $req, $args) {
            $body = $req->getParsedBody() ?? [];

            $appointmentRequest = new AppointmentRequest($body);
            $appointmentRequest->validateCreate();

            $appointment = $this->service->create($body);
            return $this->success($res, $appointment, 201);
        });
    }

    public function update(Request $req, Response $res, array $args): Response {
        return $this->respond($res, function() use ($res, $req, $args) {
            $body = $req->getParsedBody();

            $appointmentRequest = new AppointmentRequest($body);
            $appointmentRequest->validateUpdate();

            $id = (int) $args['id'] ?? 0;
            $appointment = $this->service->update(['id' => $id, 'body' => $body]);
            return $this->success($res, $appointment);
        });
    }

    public function delete(Request $req, Response $res, array $args): Response {
        return $this->respond($res, function($res) use ($args) {
            $id = (int) $args['id'] ?? 0;
            $this->service->delete(['id' => $id]);
            return $this->noContent($res);
        });
    }

}
