<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Domain;

/**
 * Durable store (S3 in production).
 */
interface MemoRepository
{
    public function save(Memo $memo): void;

    public function find(MemoId $id): ?Memo;

    /**
     * Newest first.
     *
     * @return list<Memo>
     */
    public function all(): array;
}
