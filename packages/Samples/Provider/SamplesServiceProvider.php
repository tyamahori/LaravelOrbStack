<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Provider;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\ServiceProvider;
use LaravelOrbStack\Samples\Console\DeleteMemoCommand;
use LaravelOrbStack\Samples\Console\EditMemoCommand;
use LaravelOrbStack\Samples\Console\PublishMemoCommand;
use LaravelOrbStack\Samples\Console\ShowMemoCommand;
use LaravelOrbStack\Samples\Domain\MemoCache;
use LaravelOrbStack\Samples\Domain\MemoIndex;
use LaravelOrbStack\Samples\Domain\MemoRepository;
use LaravelOrbStack\Samples\Persistence\EloquentMemoIndex;
use LaravelOrbStack\Samples\Persistence\IlluminateMemoCache;
use LaravelOrbStack\Samples\Persistence\S3MemoRepository;
use Override;

/**
 * Everything the framework needs to know about the Samples package: port
 * bindings, the "samples::" view namespace, and Artisan commands. Routes are
 * listed in bootstrap/app.php so the framework's RouteServiceProvider keeps
 * owning name lookups and route caching.
 */
final class SamplesServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->bind(MemoRepository::class, S3MemoRepository::class);
        $this->app->bind(MemoIndex::class, EloquentMemoIndex::class);
        $this->app->bind(MemoCache::class, IlluminateMemoCache::class);

        $this->commands([
            PublishMemoCommand::class,
            ShowMemoCommand::class,
            EditMemoCommand::class,
            DeleteMemoCommand::class,
        ]);
    }

    public function boot(ViewFactory $view): void
    {
        $view->addNamespace('samples', __DIR__ . '/../Http/View');
    }
}
