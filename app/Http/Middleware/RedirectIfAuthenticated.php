<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Symfony\Component\HttpFoundation\Response;

final class RedirectIfAuthenticated
{
    public function __construct(
        private Factory $auth,
        private Redirector $redirector,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->redirector->to(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
