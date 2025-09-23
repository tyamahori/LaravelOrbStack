<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use Override;

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
        return match (true) {
            Config::get('app.model_should_be_strict') => true,
            default => false,
        };
    }
}
