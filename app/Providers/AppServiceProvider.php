<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function assert;
use function is_bool;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        Date::use(CarbonImmutable::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict($this->isModelShouldBeStrict());
    }

    private function isModelShouldBeStrict(): bool
    {
        try {
            $config = $this->app->get('config');
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            report($e);

            return false;
        }

        assert($config instanceof Repository, 'Should be ' . Repository::class);

        try {
            $isModelShouldBeStrict = $config->get('app.model_should_be_strict');
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            report($e);

            return false;
        }

        assert(is_bool($isModelShouldBeStrict), 'Must be a boolean value');

        return $isModelShouldBeStrict;
    }
}
