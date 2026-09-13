<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Object-key-safe identifier. The pattern is the one guard that keeps a
 * user-supplied id from becoming a path segment like `../` on S3.
 */
final readonly class MemoId
{
    public const string PATTERN = '[0-9]{8}-[0-9]{6}-[0-9]{6}';

    public function __construct(
        public string $value,
    ) {
        if (preg_match('/\A' . self::PATTERN . '\z/', $value) !== 1) {
            throw new InvalidArgumentException("メモ ID の形式が不正です: {$value}");
        }
    }

    /**
     * Derived from the clock so ids sort by publish time and tests with a
     * fixed clock get a fixed id.
     * ponytail: two publishes inside the same microsecond collide; inject a
     * random port when that becomes a real risk.
     */
    public static function at(DateTimeImmutable $now): self
    {
        return new self($now->format('Ymd-His-u'));
    }
}
