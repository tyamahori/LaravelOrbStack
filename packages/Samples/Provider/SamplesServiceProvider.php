<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Provider;

use Illuminate\Support\ServiceProvider;
use LaravelOrbStack\Samples\Domain\MemoCache;
use LaravelOrbStack\Samples\Domain\MemoIndex;
use LaravelOrbStack\Samples\Domain\MemoRepository;
use LaravelOrbStack\Samples\Persistence\EloquentMemoIndex;
use LaravelOrbStack\Samples\Persistence\IlluminateMemoCache;
use LaravelOrbStack\Samples\Persistence\S3MemoRepository;
use Override;

/**
 * Binds the Samples ports (Domain/) to their Persistence/ implementations.
 */
final class SamplesServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->bind(MemoRepository::class, S3MemoRepository::class);
        $this->app->bind(MemoIndex::class, EloquentMemoIndex::class);
        $this->app->bind(MemoCache::class, IlluminateMemoCache::class);
    }
}
