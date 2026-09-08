<?php

declare(strict_types=1);

namespace App\Console;

use App\Foundation\Bootstrap\RegisterFacadeRoot;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Foundation\Bootstrap\SetRequestForConsole;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Override;

final class Kernel extends ConsoleKernel
{
    #[Override]
    protected $bootstrappers = [
        LoadEnvironmentVariables::class,
        LoadConfiguration::class,
        HandleExceptions::class,
        RegisterFacadeRoot::class,
        SetRequestForConsole::class,
        RegisterProviders::class,
        BootProviders::class,
    ];

    #[Override]
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
