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
| The guarded file is generated from the framework's sources, located through
| installed.json: every autoload.files entry that declares global functions,
| with a guard call opening each function body (the bodies stay the
| framework's, so once() still hashes its real call site), and the facade
| base class renamed VendorFacade, which our Facade extends to check the
| caller in __callStatic. It is a plain file rather than eval()'d code so
| opcache keeps it, and its name carries a hash of installed.json and this
| file, so any Composer change or edit here makes a new one. Static methods
| that really exist on the base class (swap(), shouldReceive(), ...) are only
| covered by LibConfig\PhpStan\NoFacadeRule.
|
*/

namespace LaravelOrbStack {
    use Composer\Autoload\ClassLoader;
    use Illuminate\Support\Facades\Facade;
    use LogicException;
    use PhpToken;
    use RuntimeException;

    /** The Composer package whose global functions and facade base class are guarded. */
    const FRAMEWORK = 'laravel/framework';

    const FACADE = Facade::class;

    /**
     * Project directories that may call facades: vendor/ is the framework
     * calling itself, config/ is evaluated before the container exists.
     */
    const ALLOWED = ['vendor/', 'config/'];

    /**
     * Helpers are also allowed from compiled Blade, which includes Laravel's
     * own error pages (they call __()) and has lost its source path.
     */
    const HELPER_ALLOWED = [...ALLOWED, 'storage/framework/views/'];

    /** Frames guard_helper() walks: enough for chains like abort_if() -> abort() -> app(). */
    const HELPER_FRAMES = 6;

    /** Path prefix of the generated file; the input hash and `.php` follow. */
    const GUARDED = __DIR__ . '/cache/guarded-framework-';

    /**
     * Throws when $frame is project code outside $allowed. The guarded file
     * counts as the framework: its helpers call facades (now() -> Date::now()).
     *
     * @param array{file?: string, line?: int} $frame
     * @param list<string> $allowed directories relative to the project root
     */
    function reject_app_call(string $call, array $frame, array $allowed): void
    {
        $file = $frame['file'] ?? '';
        $root = \dirname(__DIR__) . '/';

        if (! str_starts_with($file, $root) || str_starts_with($file, GUARDED)) {
            return;
        }

        foreach ($allowed as $directory) {
            if (str_starts_with($file, $root . $directory)) {
                return;
            }
        }

        throw new LogicException(\sprintf(
            '%s called from %s:%d: inject the underlying contract instead.',
            $call,
            $file,
            $frame['line'] ?? 0,
        ));
    }

    function guard_helper(): void
    {
        // Frame 0 is this call; frames in GUARDED are helpers calling helpers
        // (abort_if() -> abort() -> app(), ...), so the first frame outside is
        // the code that wrote the outermost helper call and its `function` is
        // the helper the user actually wrote.
        foreach (\array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, HELPER_FRAMES), 1) as $frame) {
            $file = $frame['file'] ?? null;

            if ($file === null || str_starts_with($file, GUARDED)) {
                continue;
            }

            reject_app_call(\sprintf('Global helper %s()', $frame['function']), $frame, HELPER_ALLOWED);

            return;
        }
    }

    /**
     * How far $lexeme moves the brace depth.
     */
    function nesting(PhpToken $lexeme): int
    {
        return match (true) {
            $lexeme->text === '{', $lexeme->is(T_DOLLAR_OPEN_CURLY_BRACES) => 1,
            $lexeme->text === '}' => -1,
            default => 0,
        };
    }

    /**
     * Tokens of $source without the open tag, and with each top-level
     * `if (! function_exists(...))` reduced to a bare block: this file loads
     * first, so the check always passes, and fake()'s `&& class_exists(Faker)`
     * would fail before the autoloader exists. Null when $source declares a
     * namespace: namespaced functions are not global helpers.
     *
     * @return list<PhpToken>|null
     */
    function helper_tokens(string $source): array|null
    {
        $tokens = [];
        $depth = 0;
        $condition = false;

        foreach (PhpToken::tokenize($source) as $token) {
            if ($token->is(T_NAMESPACE)) {
                return null;
            }

            // From a top-level `if` up to its `{`.
            $condition = ($condition || ($depth === 0 && $token->is(T_IF))) && $token->text !== '{';
            $depth += nesting($token);

            if (! $condition && ! $token->is(T_OPEN_TAG)) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    /**
     * $tokens as source with a guard call opening each named function body
     * that is not inside another one: anonymous-class methods in a helper are
     * not helpers.
     *
     * @param list<PhpToken> $tokens
     */
    function guard_helpers(array $tokens): string
    {
        $guarded = '';
        $depth = 0;
        $body = PHP_INT_MAX; // depth of the helper body being copied
        $signature = false;
        $recent = [null, null]; // the last two non-ignorable tokens

        foreach ($tokens as $token) {
            // `function name(`: closures have no name, `use function` no parenthesis.
            $signature = $signature || ($depth < $body && $token->text === '(' && $recent[0]?->id === T_FUNCTION && $recent[1]?->id === T_STRING);
            $depth += nesting($token);
            $body = $depth < $body ? PHP_INT_MAX : $body;
            $guarded .= $token->text;

            if ($signature && $token->text === '{') {
                $guarded .= ' \LaravelOrbStack\guard_helper();';
                $signature = false;
                $body = $depth;
            }

            if (! $token->isIgnorable()) {
                $recent = [$recent[1], $token];
            }
        }

        return $guarded;
    }

    /**
     * $source (one namespaced class file) with class $name declared as $as,
     * and its namespace braced so it can share a file.
     */
    function rename_class(string $source, string $name, string $as): string
    {
        $renamed = '';
        $namespace = false;
        $previous = null;

        foreach (PhpToken::tokenize($source) as $token) {
            $namespace = $namespace || $token->is(T_NAMESPACE);

            $renamed .= match (true) {
                $token->is(T_OPEN_TAG) => '',
                $namespace && $token->text === ';' => ' {',
                $previous?->id === T_CLASS && $token->text === $name => $as,
                default => $token->text,
            };

            $namespace = $namespace && $token->text !== ';';

            if (! $token->isIgnorable()) {
                $previous = $token;
            }
        }

        return $renamed . "}\n";
    }

    $composer = \dirname(__DIR__) . '/vendor/composer/';
    $installed = $composer . 'installed.json';

    // ponytail: files for superseded hashes stay behind in bootstrap/cache;
    // deleting them could race a request still requiring one.
    $file = GUARDED . hash('xxh128', hash_file('xxh128', __FILE__) . hash_file('xxh128', $installed)) . '.php';

    if (! is_file($file)) {
        /** @var array{packages: list<array{name: string, install-path: string, autoload: array{files: list<string>, psr-4: array<string, string|list<string>>}}>} $metadata */
        $metadata = json_decode((string) file_get_contents($installed), true, flags: JSON_THROW_ON_ERROR);
        $package = array_find($metadata['packages'], static fn (array $package): bool => $package['name'] === FRAMEWORK)
            ?? throw new RuntimeException(FRAMEWORK . ' is not installed');
        $base = $composer . $package['install-path'] . '/';
        $guarded = "<?php\n";

        // Each file gets its own braced namespace block: they share imports (Arr, ...).
        foreach ($package['autoload']['files'] as $path) {
            $tokens = helper_tokens((string) file_get_contents($base . $path));

            if ($tokens !== null) {
                $guarded .= "namespace {\n" . guard_helpers($tokens) . "}\n";
            }
        }

        // Composer's own PSR-4 lookup; the class is already declared when
        // vendor/autoload.php needs it, so its loader never loads it again.
        require_once $composer . 'ClassLoader.php';
        $loader = new ClassLoader();

        foreach ($package['autoload']['psr-4'] as $prefix => $paths) {
            $loader->addPsr4($prefix, array_map(static fn (string $path): string => $base . $path, (array) $paths));
        }

        $guarded .= rename_class(
            (string) file_get_contents((string) $loader->findFile(FACADE)),
            substr(strrchr(FACADE, '\\'), 1),
            'VendorFacade',
        );

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
                \LaravelOrbStack\ALLOWED,
            );

            return parent::__callStatic($method, $args);
        }
    }
}

namespace {
    return require __DIR__ . '/../vendor/autoload.php';
}
