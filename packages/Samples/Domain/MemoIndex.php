<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Domain;

/**
 * Listing index (PostgreSQL in production). Holds headings only; the
 * document itself lives in MemoRepository.
 */
interface MemoIndex
{
    public function add(Memo $memo): void;

    /**
     * Newest first.
     *
     * @return list<MemoHeading>
     */
    public function latest(): array;
}
