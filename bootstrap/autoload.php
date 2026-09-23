<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Global helper / facade guard
|--------------------------------------------------------------------------
|
| Every entry point requires this file instead of vendor/autoload.php.
| Laravel declares its helpers behind `function_exists()`, and Composer
| autoloads Illuminate\Support\Facades\Facade lazily, so declaring both
| here first means the framework's own files are never loaded.
|
| The guarded file is generated from the framework's sources: every helpers.php
| with a guard call injected as the first statement of each function body
| (the bodies stay the framework's, so once() still hashes its real call
| site), and Facade.php renamed to VendorFacade, which our Facade extends
| to check the caller in __callStatic. It is a plain file rather than
| eval()'d code so opcache keeps it, and its name carries a hash of
| installed.json and this file, so any Composer change or edit here makes
| a new one. Static methods that really exist on the base class
| (swap(), shouldReceive(), ...) are only covered by
| LibConfig\PhpStan\NoFacadeRule.
|
*/

namespace LaravelOrbStack {
    use LogicException;
    use RuntimeException;

    /** Path prefix of the generated file; the input hash and `.php` follow. */
    const GUARDED = __DIR__ . '/cache/guarded-framework-';

    /**
     * Throws when $frame is application code: anything under the project
     * root except vendor/ (the framework calling itself) and config/
     * (evaluated before the container exists).
     *
     * @param array{file?: string, line?: int} $frame
     */
    function reject_app_call(string $call, array $frame): void
    {
        $file = $frame['file'] ?? '';
        $root = \dirname(__DIR__) . '/';

        if (
            str_starts_with($file, $root)
            && ! str_starts_with($file, $root . 'vendor/')
            && ! str_starts_with($file, $root . 'config/')
        ) {
            throw new LogicException(\sprintf(
                '%s called from %s:%d: inject the underlying contract instead.',
                $call,
                $file,
                $frame['line'] ?? 0,
            ));
        }
    }

    function guard_helper(): void
    {
        // Frame 0 is this call; frames in GUARDED are helpers calling helpers
        // (abort_if() -> abort() -> app(), ...), so the first frame outside is
        // the code that wrote the outermost helper call and its `function` is
        // the helper the user actually wrote.
        foreach (\array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 6), 1) as $frame) {
            $file = $frame['file'] ?? null;

            if ($file === null || str_starts_with($file, GUARDED)) {
                continue;
            }

            // storage/framework/views holds compiled Blade, including Laravel's
            // own error pages (which call __()); the source path is lost there.
            if (! str_starts_with($file, \dirname(__DIR__) . '/storage/framework/views/')) {
                reject_app_call(\sprintf('Global helper %s()', $frame['function']), $frame);
            }

            return;
        }
    }

    $vendor = \dirname(__DIR__) . '/vendor/';

    // ponytail: files for superseded hashes stay behind in bootstrap/cache;
    // deleting them could race a request still requiring one.
    $file = GUARDED . hash('xxh128', hash_file('xxh128', __FILE__) . hash_file('xxh128', $vendor . 'composer/installed.json')) . '.php';

    if (! is_file($file)) {
        $framework = $vendor . 'laravel/framework/src/Illuminate/';
        $guarded = "<?php\n";

        // helpers.php files share imports (Arr, ...), so each gets its own
        // braced namespace block. fake() loses its `&& class_exists(Faker)`
        // check: the autoloader is not registered yet, so it would be false
        // here and leave the vendor copy unguarded.
        foreach ((array) glob($framework . '*/helpers.php') as $helpers) {
            $guarded .= 'namespace {' . preg_replace(
                ['/^<\?php/', '/^(if \(! function_exists\(\'\w+\'\)) && .*(\) \{)$/m', '/^    \{$/m'],
                ['', '$1$2', "    {\n        \\LaravelOrbStack\\guard_helper();"],
                (string) file_get_contents((string) $helpers),
            ) . "}\n";
        }

        $guarded .= preg_replace(
            ['/^<\?php/', '/^namespace ([\w\\\\]+);$/m', '/^abstract class Facade$/m'],
            ['', 'namespace $1 {', 'abstract class VendorFacade'],
            (string) file_get_contents($framework . 'Support/Facades/Facade.php'),
        ) . "}\n";

        // A uniquely named temp file, completely written, then renamed: other
        // requests (FrankenPHP threads share a PID) never see a partial file.
        // Not tempnam(): it silently falls back to /tmp, where rename() is a
        // non-atomic cross-device copy, and creates the file 0600.
        $tmp = $file . '.' . bin2hex(random_bytes(8));

        if (file_put_contents($tmp, $guarded) !== \strlen($guarded) || ! rename($tmp, $file)) {
            if (is_file($tmp)) {
                unlink($tmp);
            }

            throw new RuntimeException('Cannot write ' . $file);
        }
    }

    require $file;
}

namespace Illuminate\Support\Facades {
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
            // Frame 0 is the code that wrote `Foo::bar()`; the framework's own
            // facade calls (HasTimestamps -> Date::now(), ...) come from vendor/.
            // Compiled Blade stays covered here: static analysis never sees it.
            \LaravelOrbStack\reject_app_call(
                \sprintf('Facade %s::%s()', static::class, $method),
                debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0] ?? [],
            );

            return parent::__callStatic($method, $args);
        }
    }
}

namespace {
    return require __DIR__ . '/../vendor/autoload.php';
}
