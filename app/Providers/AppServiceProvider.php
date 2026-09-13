<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\DateFactory;
use Illuminate\Support\ServiceProvider;
use Override;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\NativeClock;

/**
 * Application-wide wiring only. Bindings for a package's ports live in that
 * package's provider (app/Providers/<Feature>ServiceProvider.php).
 */
final class AppServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        DateFactory::use(CarbonImmutable::class);

        $this->app->bind(ClockInterface::class, NativeClock::class);
    }

    public function boot(Repository $config): void
    {
        Model::shouldBeStrict(match (true) {
            $config->get('app.model_should_be_strict') => true,
            default => false,
        });
    }
}
