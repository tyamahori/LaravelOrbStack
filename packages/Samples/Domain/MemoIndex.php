<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Domain;

/**
 * Listing index (PostgreSQL in production). Holds headings only; the
 * document itself lives in MemoRepository.
 */
interface MemoIndex
{
    /**
     * Creates or overwrites the heading.
     */
    public function put(Memo $memo): void;

    /**
     * No-op when the heading does not exist.
     */
    public function remove(MemoId $id): void;

    /**
     * Newest first.
     *
     * @return list<MemoHeading>
     */
    public function latest(): array;
}
