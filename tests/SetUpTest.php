<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Event\Application\Started;
use PHPUnit\Event\Application\StartedSubscriber;
use PHPUnit\Runner\Extension\Extension as PhpunitExtension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class SetUpTest implements PhpunitExtension
{
    public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters
    ): void {
        $facade->registerSubscriber(
            $this->getStartedSubscriber()
        );
    }

    private function getStartedSubscriber(): StartedSubscriber
    {
        return new class() implements StartedSubscriber
        {
            public function notify(Started $event): void
            {
                echo shell_exec('DB_SCHEMA=test php artisan db:wipe');
            }
        };
    }
}
