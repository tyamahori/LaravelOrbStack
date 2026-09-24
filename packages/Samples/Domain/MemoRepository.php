<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Domain;

use JsonException;

/**
 * Document store (S3 in production). Listing goes through MemoIndex.
 */
interface MemoRepository
{
    /**
     * Creates or overwrites.
     *
     * @throws JsonException
     */
    public function save(Memo $memo): void;

    /**
     * @throws JsonException
     */
    public function find(MemoId $id): Memo|null;

    /**
     * No-op when the memo does not exist.
     */
    public function delete(MemoId $id): void;
}
