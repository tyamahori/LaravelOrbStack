<?php

declare(strict_types=1);

namespace App\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Facade;

final class RegisterFacadeRoot
{
    public function bootstrap(Application $app): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($app);
    }
}
