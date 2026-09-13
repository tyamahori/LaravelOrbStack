<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Domain;

use DateTimeImmutable;

/**
 * What a listing needs; the body stays in the repository.
 */
final readonly class MemoHeading
{
    public function __construct(
        public MemoId $id,
        public string $title,
        public DateTimeImmutable $publishedAt,
    ) {
    }
}
