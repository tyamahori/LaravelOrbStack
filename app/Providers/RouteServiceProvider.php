<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Override;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     */
    final public const string HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    #[Override]
    public function boot(): void
    {
        // 利用する明確は理由がアレば使う
        //        RateLimiter::for(
        //            'api',
        //            static fn (Request $request) => Limit::perMinute(60)
        //                ->by($request->user() !== null ? $request->user()->id : $request->ip())
        //        );

        $this->routes(static function (): void {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
