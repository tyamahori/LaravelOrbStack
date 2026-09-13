<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\DateFactory;
use Illuminate\Support\ServiceProvider;
use LaravelOrbStack\Samples\Domain\MemoCache;
use LaravelOrbStack\Samples\Domain\MemoRepository;
use LaravelOrbStack\Samples\Persistence\IlluminateMemoCache;
use LaravelOrbStack\Samples\Persistence\S3MemoRepository;
use Override;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\NativeClock;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        DateFactory::use(CarbonImmutable::class);

        $this->app->bind(ClockInterface::class, NativeClock::class);
        $this->app->bind(MemoRepository::class, S3MemoRepository::class);
        $this->app->bind(MemoCache::class, IlluminateMemoCache::class);
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
