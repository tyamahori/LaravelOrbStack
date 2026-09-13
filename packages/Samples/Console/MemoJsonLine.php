<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Console;

use DateTimeImmutable;
use LaravelOrbStack\Samples\Domain\Memo;

/**
 * The one record shape every `--json` command prints, so output of one
 * command can feed another without reshaping.
 */
final readonly class MemoJsonLine
{
    public static function of(Memo $memo): string
    {
        return json_encode([
            'id' => $memo->id->value,
            'title' => $memo->title,
            'body' => $memo->body,
            'published_at' => $memo->publishedAt->format(DateTimeImmutable::RFC3339_EXTENDED),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
