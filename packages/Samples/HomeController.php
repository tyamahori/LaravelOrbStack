<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples;

use Carbon\CarbonImmutable;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Psr\Log\LoggerInterface;

use function assert;
use function is_string;

final class HomeController
{
    public function home(
        Application $app,
        Guard $auth,
        Repository $config,
        Router $router,
        UrlGenerator $url,
        CarbonImmutable $carbonImmutable,
        LoggerInterface $logger,
        Factory $view
    ): Renderable {
        $appUrl = $config->get('app.url');
        assert(is_string($appUrl), 'appUrl should be string.');
        $route = $url->route('welcome');

        $logger
            ->info(
                'debug',
                [
                    $appUrl,
                    $route,
                    $carbonImmutable->toString(),
                ]
            );

        return $view->make('welcome', [
            'locale' => $app->getLocale(),
            'authenticated' => $auth->check(),
            'homeUrl' => $url->to('/home'),
            'loginUrl' => $router->has('login') ? $url->route('login') : null,
            'registerUrl' => $router->has('register') ? $url->route('register') : null,
        ]);
    }
}
