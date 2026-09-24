<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Guarded framework generator
|--------------------------------------------------------------------------
|
| Run by Composer's post-autoload-dump, before anything boots the framework.
| Writes bootstrap/cache/guarded-framework-<hash>.php, which
| bootstrap/autoload.php requires ahead of vendor/autoload.php: every
| autoload.files entry of the framework that declares global functions, with
| a guard call opening each function body (the bodies stay the framework's,
| so once() still hashes its real call site).
| The hash covers installed.json and this file, so autoload.php can tell a
| missing or stale run from a current one.
|
*/

namespace LaravelOrbStack {
    use PhpToken;
    use RuntimeException;

    use function dirname;
    use function strlen;

    /** The Composer package whose global functions are guarded. */
    const FRAMEWORK = 'laravel/framework';

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
     * `if (! function_exists(...))` reduced to a bare block: the guarded file
     * loads first, so the check always passes, and fake()'s
     * `&& class_exists(Faker)` would fail before the autoloader exists. Null
     * when $source declares a namespace: namespaced functions are not global
     * helpers.
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

    $composer = dirname(__DIR__) . '/vendor/composer/';
    $installed = $composer . 'installed.json';

    // Same name as bootstrap/autoload.php computes.
    // ponytail: files for superseded hashes stay behind in bootstrap/cache;
    // deleting them could race a request still requiring one.
    $file = __DIR__ . '/cache/guarded-framework-' . hash('xxh128', hash_file('xxh128', __FILE__) . hash_file('xxh128', $installed)) . '.php';

    /** @var array{packages: list<array{name: string, install-path: string, autoload: array{files: list<string>}}>} $metadata */
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

    // A uniquely named temp file, completely written, then renamed: requests
    // served while Composer runs never see a partial file. Not tempnam(): it
    // silently falls back to /tmp, where rename() is a non-atomic cross-device
    // copy, and creates the file 0600.
    $tmp = $file . '.' . bin2hex(random_bytes(8));

    if (file_put_contents($tmp, $guarded) !== strlen($guarded) || ! rename($tmp, $file)) {
        if (is_file($tmp)) {
            unlink($tmp);
        }

        throw new RuntimeException('Cannot write ' . $file);
    }
}
