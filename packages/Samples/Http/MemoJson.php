<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Http;

use LaravelOrbStack\Samples\Domain\Memo;
use LaravelOrbStack\Samples\Domain\MemoHeading;

/**
 * The API's wire shape. Clients depend on these keys, so this is a
 * compatibility boundary: add keys freely, rename or drop them only with a
 * migration. Timestamps keep microseconds because the id does.
 */
final readonly class MemoJson
{
    private const string TIMESTAMP = 'Y-m-d\TH:i:s.uP';

    /**
     * @return array{id: string, title: string, body: string, published_at: string}
     */
    public static function memo(Memo $memo): array
    {
        return [
            'id' => $memo->id->value,
            'title' => $memo->title,
            'body' => $memo->body,
            'published_at' => $memo->publishedAt->format(self::TIMESTAMP),
        ];
    }

    /**
     * @return array{id: string, title: string, published_at: string}
     */
    public static function heading(MemoHeading $heading): array
    {
        return [
            'id' => $heading->id->value,
            'title' => $heading->title,
            'published_at' => $heading->publishedAt->format(self::TIMESTAMP),
        ];
    }
}
