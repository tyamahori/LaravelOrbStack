<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Domain;

/**
 * Read-through cache in front of MemoRepository. Entries may vanish at any
 * time; callers fall back to the repository.
 */
interface MemoCache
{
    public function remember(Memo $memo): void;

    public function get(MemoId $id): Memo|null;

    public function forget(MemoId $id): void;
}
