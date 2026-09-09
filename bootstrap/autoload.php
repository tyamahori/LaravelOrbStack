<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Global helper / facade guard
|--------------------------------------------------------------------------
|
| Every entry point requires this file instead of vendor/autoload.php.
| Laravel declares its helpers behind `function_exists()`, so defining
| app() here first means the framework's copy is never loaded. All the
| service-locating helpers (route(), config(), view(), auth(), ...) funnel
| through app(), so guarding this one function is enough to reject them
| from user code while leaving the framework itself untouched.
|
| Helpers that never touch the container (collect(), now(), env(), ...)
| are only covered by LibConfig\PhpStan\NoGlobalHelperRule.
|
| Facades get the same treatment at class level: Composer autoloads
| lazily, so declaring Illuminate\Support\Facades\Facade here means the
| framework's file is never loaded. The vendor class is evaluated under
| the name VendorFacade and our Facade extends it, adding the backtrace
| check to __callStatic. Static methods that really exist on the base
| class (swap(), shouldRecenive(), ...) are only covered by
| LibConfig\PhpStan\NoFacadeRule.
|
*/

namespace Illuminate\Support\Facades {
    use LogicException;

    // @mago-expect lint:no-eval
    eval(str_replace(
        ['<?php', 'abstract class Facade'],
        ['', 'abstract class VendorFacade'],
        (string) file_get_contents(\dirname(__DIR__) . '/vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php'),
    ));

    /**
     * @mago-expect lint:file-name
     */
    abstract class Facade extends VendorFacade
    {
        /**
         * @param array<int, mixed> $args
         */
        public static function __callStatic($method, $args): mixed
        {
            $root = \dirname(__DIR__) . '/';

            // Frame 0 is the code that wrote `Foo::bar()`; the framework's own
            // facade calls (HasTimestamps -> Date::now(), ...) come from vendor/.
            $frame = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0] ?? [];
            $file = $frame['file'] ?? '';

            if (
                str_starts_with($file, $root)
                && ! str_starts_with($file, $root . 'vendor/')
                && ! str_starts_with($file, $root . 'config/')
            ) {
                throw new LogicException(\sprintf(
                    'Facade %s::%s() called from %s:%d: inject the underlying contract instead.',
                    static::class,
                    $method,
                    $file,
                    $frame['line'] ?? 0,
                ));
            }

            return parent::__callStatic($method, $args);
        }
    }
}

namespace {
    use Illuminate\Container\Container;
    use Illuminate\Contracts\Container\BindingResolutionException;

    /**
     * @param class-string|string|null $abstract
     * @param array<string, mixed> $parameters
     *
     * @throws BindingResolutionException
     */
    function app(string|null $abstract = null, array $parameters = []): mixed
    {
        $root = dirname(__DIR__) . '/';

        // Frame 0 is the call to app(); walk out through the framework's helper
        // files (route() -> app(), abort_if() -> abort() -> app(), ...) so the
        // frame we judge is the code that invoked the outermost helper, and
        // that frame's `function` is the helper the user actually wrote.
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6) as $frame) {
            $file = $frame['file'] ?? null;

            if ($file === null) {
                continue;
            }

            if (str_contains($file, '/laravel/framework/') && str_ends_with($file, '/helpers.php')) {
                continue;
            }

            // storage/framework/views holds compiled Blade, including Laravel's
            // own error pages (which call __()); the source path is lost there.
            if (
                str_starts_with($file, $root)
                && ! str_starts_with($file, $root . 'vendor/')
                && ! str_starts_with($file, $root . 'config/')
                && ! str_starts_with($file, $root . 'storage/framework/views/')
            ) {
                throw new LogicException(sprintf(
                    'Global helper %s() called from %s:%d: inject the underlying contract instead.',
                    $frame['function'],
                    $file,
                    $frame['line'] ?? 0,
                ));
            }

            break;
        }

        if ($abstract === null) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }

    return require __DIR__ . '/../vendor/autoload.php';
}
