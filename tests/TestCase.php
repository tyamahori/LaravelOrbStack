<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Override;

abstract class TestCase extends BaseTestCase
{
    /**
     * Laravel's error handler routes deprecations to a log that is silenced
     * while running unit tests, so failOnDeprecation never sees them. Throw
     * instead; LOG_DEPRECATIONS_WHILE_TESTING would only write a log line.
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutDeprecationHandling();
    }
}
