<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Domain;

/**
 * Document store (S3 in production). Listing goes through MemoIndex.
 */
interface MemoRepository
{
    public function save(Memo $memo): void;

    public function find(MemoId $id): Memo|null;
}
