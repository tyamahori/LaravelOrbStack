<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use Illuminate\Support\Facades\Config;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FacadeGuardTest extends TestCase
{
    #[Test]
    public function ユーザーコードからのファサード呼び出しは実行時に拒否される(): void
    {
        // Called through a variable so LibConfig\PhpStan\NoFacadeRule
        // does not reject this file; the runtime guard still sees Config::get().
        $config = Config::class;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIsOrContains('Facade ' . Config::class . '::get() called from ' . __FILE__);

        $config::get('app.name');
    }
}
