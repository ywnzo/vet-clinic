<?php
declare(strict_types=1);
namespace App\Middleware;

use Slim\App;

class ErrorMiddleware {
    public static function register(App $app): void {
        $errorMiddleware = $app->addErrorMiddleware(APP_ENV === 'dev', true, true);
        $defaultHandler = $errorMiddleware->getDefaultErrorHandler();

        $errorMiddleware->setDefaultErrorHandler(
            function ($request, \Throwable $e, bool $displayError, bool $logError, bool $logErrorDetails) use ($defaultHandler) {
                $response = $defaultHandler($request, $e, $displayError, $logError, $logErrorDetails);
                return $response->withHeader('Content-Type', 'application/json');
            });
    }
}
