<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GlobalHelperGuardTest extends TestCase
{
    #[Test]
    public function ユーザーコードからのグローバルヘルパ呼び出しは実行時に拒否される(): void
    {
        // Called through a string so LibConfig\PhpStan\NoGlobalHelperRule
        // does not reject this file; the runtime guard still sees route().
        $route = 'route';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('Global helper route() called from ' . __FILE__);

        $route('welcome');
    }
}
