<?php
declare(strict_types=1);
namespace App\Core;

use App\Service\PolicyService;
use App\ORM\ORM;
use App\Exception\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthorizationHandler {
    public function __construct(private PolicyService $policyService) {}

    public function authorize(Request $req, array $payload): void {
        $path = $req->getUri()->getPath();
        $method = $req->getMethod();

        $config = AuthorizationConfig::fromPath($path);
        if($config === null) {
            return;
        }

        $policy = $this->policyService->getPolicy($config->getResource(), $payload);
        $action = $this->determineAction($path, $method, $config->getPathPrefix());
        $resourceId = $this->extractResourceID($path, $config->getPathPrefix());

        if(($method === 'GET' || $method === 'PUT' || $method === 'DELETE') && $resourceId) {
            $entity = $this->fetchEntity($config->getEntityClass(), $resourceId);
            $ownerId = $entity->{$config->getOwnerField()};
            $policy->authorize($action, $ownerId);
        } else {
            $policy->authorize($action);
        }
    }

    private function determineAction(string $path, string $method, string $prefix): string {
        $relativePath = substr($path, \strlen($prefix));
        return match($method) {
            'GET' => $this->hasResourceID($relativePath) ? 'show' : 'index',
            'POST' => str_contains($relativePath, '/search') ? 'search' : 'create',
            'PUT' => 'update',
            'DELETE' => 'delete',
            default => 'index',
        };
    }

    private function extractResourceID(string $path, string $prefix): ?int {
        $relativePath = substr($path, \strlen($prefix));
        $pathParts = array_filter(explode('/', $relativePath));
        $lastPart = end($pathParts);
        return (int) $lastPart;
    }

    private function hasResourceID(string $relativePath): bool {
        $pathParts = array_filter(explode('/', $relativePath));
        $lastPart = end($pathParts);
        return is_numeric($lastPart) && (int) $lastPart > 0;
    }

    private function fetchEntity(string $entityClass, int $id): ORM {
        $entity = $entityClass::findByID($id);
        if ($entity === null) {
            throw new UnauthorizedException("Entity not found", 404);
        }
        return $entity;
    }
}
