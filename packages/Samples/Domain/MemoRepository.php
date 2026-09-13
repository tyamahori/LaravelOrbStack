<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Domain;

/**
 * Document store (S3 in production). Listing goes through MemoIndex.
 */
interface MemoRepository
{
    /**
     * Creates or overwrites.
     */
    public function save(Memo $memo): void;

    public function find(MemoId $id): Memo|null;

    /**
     * No-op when the memo does not exist.
     */
    public function delete(MemoId $id): void;
}
