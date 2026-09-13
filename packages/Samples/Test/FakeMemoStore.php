<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use LaravelOrbStack\Samples\Domain\Memo;
use LaravelOrbStack\Samples\Domain\MemoCache;
use LaravelOrbStack\Samples\Domain\MemoHeading;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoIndex;
use LaravelOrbStack\Samples\Domain\MemoRepository;
use Override;

/**
 * Array-backed fake for all three ports; one class because they share the shape.
 */
final class FakeMemoStore implements MemoRepository, MemoIndex, MemoCache
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
    public function put(Memo $memo): void
    {
        $this->save($memo);
    }

    #[Override]
    public function remember(Memo $memo): void
    {
        $this->save($memo);
    }

    #[Override]
    public function find(MemoId $id): Memo|null
    {
        return $this->memos[$id->value] ?? null;
    }

    #[Override]
    public function get(MemoId $id): Memo|null
    {
        return $this->find($id);
    }

    #[Override]
    public function delete(MemoId $id): void
    {
        unset($this->memos[$id->value]);
    }

    #[Override]
    public function remove(MemoId $id): void
    {
        $this->delete($id);
    }

    #[Override]
    public function forget(MemoId $id): void
    {
        $this->delete($id);
    }

    #[Override]
    public function latest(): array
    {
        $memos = $this->memos;
        krsort($memos, SORT_STRING);

        return array_values(array_map(static fn (Memo $memo): MemoHeading => $memo->heading(), $memos));
    }
}
