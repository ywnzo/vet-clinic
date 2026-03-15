<?php
declare(strict_types=1);
namespace App\Controller;

use App\Exception\AppException;
use App\Exception\DatabaseException;
use App\Exception\NotFoundException;
use App\Exception\ValidationException;

use App\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use App\Core\Logger;


abstract class BaseController {
    protected LoggerInterface $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    abstract protected function index(Request $req, Response $res): Response;
    abstract protected function find(Request $req, Response $res, array $args = []): Response;
    abstract protected function create(Request $req, Response $res): Response;
    abstract protected function update(Request $req, Response $res, array $args = []): Response;
    abstract protected function delete(Request $req, Response $res, array $args = []): Response;


    protected function respond(Response $res, callable $callback): Response {
        try {
            return $callback($res);
        } catch (AppException $e) {
            return $this->error($res, $e->getUserMessage(), $e->getStatusCode(), $e);
        } catch(\PDOException $e) {
            $dbException = new DatabaseException($e->getMessage(), $e);
            return $this->error($res, $dbException->getUserMessage(), 500, $e);
        } catch (ValidationException $e) {
            return $this->error($res, $e->getMessage(), 500, $e);
        } catch (NotFoundException $e) {
            return $this->error($res, $e->getMessage(), 404, $e);
        } catch (\Throwable $e) {
            return $this->error($res, 'Internal Server Error', 500, $e);
        }
    }

    protected function success(Response $res, mixed $data, int $statusCode = 200): Response {
        return ApiResponse::success($res, $data, $statusCode);
    }

    protected function paginated(Response $res, array $data, int $count, int $page = 1, int $perPage = 10): Response {
        return ApiResponse::paginated($res, $data, $count, $page, $perPage);
    }

    protected function error(Response $res, string $message, int $statusCode, ?\Throwable $e = null): Response {
        if($e) {
            if($statusCode === 500) {
                $this->logger->error($message, ['exception' => $e]);
            } else {
                $this->logger->warning($message, ['exception' => $e]);
            }
        }

        $debug = [];
        if(APP_ENV !== 'production' && $e) {
            $debug = [
                'exception' => \get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }

        return ApiResponse::error($res, $message, $statusCode, $debug);
    }

    protected function noContent(Response $res): Response {
        return ApiResponse::noContent($res);
    }
}
