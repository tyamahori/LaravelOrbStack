<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Random\RandomException;

class ExampleTest extends TestCase
{
    /**
     * @test
     * @throws RandomException
     */
    public function someHoge(): void
    {
        self::assertTrue((bool)random_int(0, 1));
    }
}
