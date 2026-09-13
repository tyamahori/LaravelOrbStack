<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use LaravelOrbStack\Common\Provider\AppServiceProvider;
use LaravelOrbStack\Samples\Domain\MemoNotFound;
use LaravelOrbStack\Samples\Provider\SamplesServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [__DIR__ . '/../packages/Samples/Http/routes.php'],
    )
    ->withProviders([
        AppServiceProvider::class,
        SamplesServiceProvider::class,
    ], withBootstrapProviders: false)
    ->withEvents(discover: false)
    ->withMiddleware(static function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '**',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );
    })
    ->withExceptions(static function (Exceptions $exceptions): void {
        // Handler::map() with a class-string calls new $to('', 0, $previous); Symfony's
        // HttpException takes ($message, $previous, $code), so build it explicitly.
        $exceptions->map(
            MemoNotFound::class,
            static fn (MemoNotFound $e): NotFoundHttpException => new NotFoundHttpException($e->getMessage(), $e),
        );
    })
    ->create();
