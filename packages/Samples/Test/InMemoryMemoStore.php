<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use LaravelOrbStack\Samples\Domain\Memo;
use LaravelOrbStack\Samples\Domain\MemoCache;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoRepository;
use Override;

/**
 * Array-backed fake for both ports; one class because they share the shape.
 */
final class InMemoryMemoStore implements MemoRepository, MemoCache
{
    /**
     * @var array<string, Memo>
     */
    public array $memos = [];

    #[Override]
    public function save(Memo $memo): void
    {
        $this->memos[$memo->id->value] = $memo;
    }

    #[Override]
    public function remember(Memo $memo): void
    {
        $this->save($memo);
    }

    #[Override]
    public function find(MemoId $id): ?Memo
    {
        return $this->memos[$id->value] ?? null;
    }

    #[Override]
    public function get(MemoId $id): ?Memo
    {
        return $this->find($id);
    }

    #[Override]
    public function all(): array
    {
        $memos = $this->memos;
        krsort($memos, SORT_STRING);

        return array_values($memos);
    }
}
