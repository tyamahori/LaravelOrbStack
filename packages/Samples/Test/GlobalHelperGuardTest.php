<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use Closure;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;
use Tests\TestCase;

use function array_slice;
use function dirname;

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

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function フレームワークの全ヘルパが実行時ガードの対象になる(): void
    {
        $unguarded = [];

        foreach ((array) glob(dirname(__DIR__, 3) . '/vendor/laravel/framework/src/Illuminate/*/helpers.php') as $file) {
            preg_match_all('/^ {4}function (\w+)\(/m', (string) file_get_contents((string) $file), $matches);

            foreach ($matches[1] as $helper) {
                $function = new ReflectionFunction($helper);
                $arguments = array_map(
                    $this->dummyArgument(...),
                    array_slice($function->getParameters(), 0, $function->getNumberOfRequiredParameters()),
                );

                try {
                    $function->getClosure()(...$arguments);
                } catch (LogicException) {
                    continue;
                }

                $unguarded[] = $helper;
            }
        }

        self::assertSame([], $unguarded);
    }

    /**
     * Satisfies the parameter type so the call reaches the guard, which
     * runs before the body and never looks at the arguments.
     *
     * @return array{}|(Closure(): null)|string
     */
    private function dummyArgument(ReflectionParameter $parameter): array|Closure|string
    {
        $type = (string) $parameter->getType();

        return match (true) {
            str_contains($type, 'callable') => static fn (): null => null,
            str_contains($type, 'array') => [],
            default => '',
        };
    }
}
