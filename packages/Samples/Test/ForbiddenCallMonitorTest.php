<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\Test;
use Tests\ForbiddenCallMonitor;
use Tests\TestCase;

/**
 * Calls go through variables so LibConfig\PhpStan\NoGlobalHelperRule and
 * NoFacadeRule do not reject this file. violations() clears what it reads,
 * so these calls do not also fail the run.
 */
final class ForbiddenCallMonitorTest extends TestCase
{
    #[Test]
    public function テスト中のグローバルヘルパ呼び出しは呼び出し元付きで記録される(): void
    {
        $route = 'route';
        $route('welcome');
        $line = __LINE__ - 1;

        self::assertSame(
            ['route() called from ' . __FILE__ . ':' . $line . ': inject the underlying contract instead.'],
            ForbiddenCallMonitor::violations(),
        );
    }

    #[Test]
    public function テスト中のファサード呼び出しは呼び出し元付きで記録される(): void
    {
        $config = Config::class;
        $config::get('app.name');
        $line = __LINE__ - 1;

        self::assertSame(
            [Facade::class . '::__callStatic() called from ' . __FILE__ . ':' . $line . ': inject the underlying contract instead.'],
            ForbiddenCallMonitor::violations(),
        );
    }
}
