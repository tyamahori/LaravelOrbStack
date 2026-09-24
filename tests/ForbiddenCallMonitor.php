<?php

declare(strict_types=1);

namespace Tests;

use const DIRECTORY_SEPARATOR;
use Illuminate\Support\Facades\Facade as LaravelFacade;
use LogicException;
use Override;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;
use function dirname;
use function function_exists;
use function in_array;
use function is_array;
use function sprintf;

/**
 * Fails the run when project code calls a Laravel global helper or facade
 * during a test. Xdebug's function monitor records each call site, so the
 * framework runs unmodified and production has no guard to trip over; code no
 * test executes is left to LibConfig\PhpStan\NoGlobalHelperRule and
 * NoFacadeRule.
 */
final class ForbiddenCallMonitor implements Extension
{
    /**
     * Directories that may call them: vendor/ is the framework calling
     * itself, config/ is evaluated before the container exists, and compiled
     * Blade includes Laravel's own error pages (they call __()).
     */
    private const array ALLOWED = ['vendor/', 'config/', 'storage/framework/views/'];

    #[Override]
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        // Without develop mode the monitor silently records nothing.
        // @mago-expect lint:no-debug-symbols
        $modes = function_exists('xdebug_info') ? xdebug_info('mode') : [];

        if (! is_array($modes) || ! in_array('develop', $modes, true)) {
            throw new RuntimeException('Run PHPUnit through `composer phpunit`: forbidden-call detection needs xdebug.mode=develop.');
        }

        // One monitor for the whole run: Xdebug refuses to restart a running
        // one, so each test only drains what it recorded.
        // @mago-expect lint:no-debug-symbols
        xdebug_start_function_monitor($this->monitored());

        $facade->registerSubscriber(
            new class() implements FinishedSubscriber
            {
                #[Override]
                public function notify(Finished $event): void
                {
                    $violations = ForbiddenCallMonitor::violations();

                    if ($violations !== []) {
                        // PHPUnit reports a subscriber's exception as a warning,
                        // which fails the run (failOnPhpunitWarning).
                        throw new LogicException($event->test()->id() . ":\n" . implode("\n", $violations));
                    }
                }
            },
        );
    }

    /**
     * Forbidden calls recorded since the last call, cleared as they are read.
     *
     * @return list<string>
     */
    public static function violations(): array
    {
        // Not `. '/'`: Rector would fold that into `__DIR__ . '/../'`, which
        // no recorded path starts with.
        $root = dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $violations = [];

        // Only the clear flag empties the record; stopping and restarting the
        // monitor keeps it. The stubs PHPStan and Rector read predate the
        // flag (Xdebug 3.1).
        // @mago-expect lint:no-debug-symbols
        /** @var list<array{function: string, filename: string, lineno: int}> $calls */
        $calls = xdebug_get_monitored_functions(true); // @phpstan-ignore arguments.count (stub predates the clear flag)

        foreach ($calls as $call) {
            $file = $call['filename'];

            if (! str_starts_with($file, $root) || array_any(self::ALLOWED, static fn (string $directory): bool => str_starts_with($file, $root . $directory))) {
                continue;
            }

            $violations[] = sprintf('%s() called from %s:%d: inject the underlying contract instead.', $call['function'], $file, $call['lineno']);
        }

        return $violations;
    }

    /**
     * Every function laravel/framework declares (helpers.php and the
     * namespaced functions.php), and every static entry point of the facade
     * base class: __callStatic() for proxied calls, plus the real static
     * methods such as swap() and shouldReceive().
     *
     * @return list<string>
     */
    private function monitored(): array
    {
        $framework = dirname((string) new ReflectionClass(LaravelFacade::class)->getFileName(), 3) . DIRECTORY_SEPARATOR;
        $monitored = [];

        foreach (get_defined_functions()['user'] as $name) {
            $function = new ReflectionFunction($name);

            if (str_starts_with((string) $function->getFileName(), $framework)) {
                $monitored[] = $function->getName();
            }
        }

        foreach (new ReflectionClass(LaravelFacade::class)->getMethods(ReflectionMethod::IS_STATIC) as $method) {
            if (! $method->isPublic() || $method->class !== LaravelFacade::class) {
                continue;
            }

            $monitored[] = LaravelFacade::class . '::' . $method->getName();
        }

        return $monitored;
    }
}
