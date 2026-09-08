<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Routing\Router;
use Override;

final class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     */
    public const string HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    #[Override]
    public function boot(): void
    {
        $this->routes(function (Router $router): void {
            $router->group(
                ['middleware' => 'api', 'prefix' => 'api'],
                $this->app->basePath('routes/api.php'),
            );
            $router->group(['middleware' => 'web'], $this->app->basePath('routes/web.php'));
        });
    }
}
