<?php

declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Exception\HttpNotFoundException;

return function (App $app) {

    /**
     * The routing middleware should be added earlier than the ErrorMiddleware
     * Otherwise exceptions thrown from it will not be handled by the middleware
     */
    $app->addRoutingMiddleware();

    $app->addMiddleware(
        new Middlewares\TrailingSlash
    );

    /**
     * Add Error Middleware
     *
     * @param  bool  $displayErrorDetails  -> Should be set to false in production
     * @param  bool  $logErrors  -> Parameter is passed to the default ErrorHandler
     * @param  bool  $logErrorDetails  -> Display error details in error log
     * @param  LoggerInterface|null  $logger  -> Optional PSR-3 Logger
     *
     * Note: This middleware should be added last. It will not handle any exceptions/errors
     * for middleware added after it.
     */
    $errorMiddleware = $app->addErrorMiddleware(
        env('DISPLAY_ERRORS', false),
        env('LOG_ERRORS', false),
        env('LOG_ERROR_DETAILS', false)
    );

    // Add a custom handler for 404 not found exception for routes
    $customNotFoundHandler = function (
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use ($app) {
        $response = $app->getResponseFactory()->createResponse();
        $payload = ['error' => 'Endpoint not found'];
        $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404);
    };

    // Después de addErrorMiddleware(...)
    $errorMiddleware->setErrorHandler(
        HttpNotFoundException::class,
        $customNotFoundHandler
    );

};
