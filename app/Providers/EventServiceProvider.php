<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Override;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @inheritDoc
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * @inheritDoc
     */
    #[Override]
    public function boot(): void
    {

    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
