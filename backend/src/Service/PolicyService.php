<?php
declare(strict_types=1);
namespace App\Service;

use App\Policies\PolicyInterface;
use App\Exception\UnauthorizedException;

class PolicyService {
    private array $policie;

    public function register(string $resource, string $policyClass): void {
        $this->policie[$resource] = $policyClass;
    }

    public function authorize(string $resource, string $action, ...$params): void {
        if (!isset($this->policie[$resource])) {
            throw new UnauthorizedException("No policy registered for resource: {$resource}", 403);
        }

        $policyClass = $this->policie[$resource];
        $policy = new $policyClass();

        if(!empty($params)) {
            $policy->authorize($action, ...$params);
        } else {
            $policy->authorize($action);
        }
    }

    public function getPolicy(string $resource, array $auth): ?PolicyInterface {
        if (!isset($this->policie[$resource])) {
            throw new UnauthorizedException("No policy registered for resource: {$resource}", 403);
        }

        $policyClass = $this->policie[$resource];
        return new $policyClass($auth);
    }
}
