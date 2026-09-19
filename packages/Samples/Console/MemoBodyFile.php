<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Console;

use const PATHINFO_FILENAME;
use const STDIN;

use function is_readable;
use function is_string;
use function pathinfo;
use function stream_get_contents;

/**
 * `memo:publish` and `memo:edit` both take a body file (`-` for stdin) and
 * an optional `--title` that falls back to the file name.
 */
final readonly class MemoBodyFile
{
    /**
     * @return string|null null when the path is neither `-` nor readable
     */
    public static function read(string $path): string|null
    {
        $body = match (true) {
            $path === '-' => stream_get_contents(STDIN),
            is_readable($path) => file_get_contents($path),
            default => false,
        };

        return is_string($body) ? $body : null;
    }

    public static function title(string|null $option, string $path): string
    {
        return is_string($option) ? $option : pathinfo($path, PATHINFO_FILENAME);
    }
}
