<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\DateFactory;
use Illuminate\Support\ServiceProvider;
use Override;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        DateFactory::use(CarbonImmutable::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Repository $config): void
    {
        Model::shouldBeStrict(match (true) {
            $config->get('app.model_should_be_strict') => true,
            default => false,
        });
    }
}
