<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use Illuminate\Support\Facades\Config;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function dirname;

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

    #[Test]
    public function ファサード基底クラスの複製はガード以外vendorと一致する(): void
    {
        // bootstrap/Facade.php replaces vendor's by classmap; a Laravel update
        // that changes the original must be re-copied by hand.
        $root = dirname(__DIR__, 3);
        $copy = (string) file_get_contents($root . '/bootstrap/Facade.php');

        self::assertSame(
            file_get_contents($root . '/vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php'),
            preg_replace('#^ *// LaravelOrbStack guard: begin\n.*?// LaravelOrbStack guard: end\n#ms', '', $copy),
        );
    }
}
