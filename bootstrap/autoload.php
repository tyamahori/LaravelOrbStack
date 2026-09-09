<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Global helper / facade guard
|--------------------------------------------------------------------------
|
| Every entry point requires this file instead of vendor/autoload.php.
| Laravel declares its helpers behind `function_exists()`, so defining
| them here first means the framework's copies are never loaded. Each
| laravel/framework helpers.php is evaluated with a guard call injected
| as the first statement of every function body; the bodies themselves
| are the framework's own, so once() still hashes its real call site and
| mix() still sees its real func_get_args().
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

namespace LaravelOrbStack {
    use LogicException;

    function guard_helper(): void
    {
        $root = \dirname(__DIR__) . '/';

        // Frame 0 is this call; frames whose file is this file's eval()'d code
        // are helpers calling helpers (abort_if() -> abort() -> app(), ...), so
        // the first frame outside is the code that wrote the outermost helper
        // call and its `function` is the helper the user actually wrote.
        foreach (\array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6), 1) as $frame) {
            $file = $frame['file'] ?? null;

            if ($file === null || str_starts_with($file, __FILE__)) {
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
                throw new LogicException(\sprintf(
                    'Global helper %s() called from %s:%d: inject the underlying contract instead.',
                    $frame['function'],
                    $file,
                    $frame['line'] ?? 0,
                ));
            }

            return;
        }
    }

    // The autoloader is not registered yet, so `&& class_exists(Faker...)`
    // on fake() would be false here and leave the vendor copy unguarded.
    // ponytail: re-parsed on every request (eval bypasses opcache); write the
    // guarded source to storage/framework and require it if that ever profiles.
    foreach ((array) glob(\dirname(__DIR__) . '/vendor/laravel/framework/src/Illuminate/*/helpers.php') as $helpers) {
        // @mago-expect lint:no-eval
        eval((string) preg_replace(
            ['/^<\?php/', '/^(if \(! function_exists\(\'\w+\'\)) && .*(\) \{)$/m', '/^    \{$/m'],
            ['', '$1$2', '    {' . "\n" . '        \LaravelOrbStack\guard_helper();'],
            (string) file_get_contents((string) $helpers),
        ));
    }
}

namespace {
    return require __DIR__ . '/../vendor/autoload.php';
}
