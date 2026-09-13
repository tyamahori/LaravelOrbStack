<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Http;

use const PHP_SAPI;
use const PHP_VERSION;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;

final class HomeController
{
    public function home(Factory $view): Renderable
    {
        return $view->make('samples::welcome', [
            'sapi' => PHP_SAPI,
            'runtime' => match (PHP_SAPI) {
                'frankenphp' => 'FrankenPHP',
                'apache2handler' => 'Apache mod_php',
                default => PHP_SAPI,
            },
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    }
}
