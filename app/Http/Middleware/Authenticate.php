<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Override;

final class Authenticate extends Middleware
{
    public function __construct(
        Factory $auth,
        private readonly UrlGenerator $url,
    ) {
        parent::__construct($auth);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    #[Override]
    protected function redirectTo(Request $request): string|null
    {
        return match(true) {
            $request->expectsJson() => null,
            default => $this->url->route('login'),
        };
    }
}
