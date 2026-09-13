<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\UseCase;

use LaravelOrbStack\Samples\Domain\Memo;
use LaravelOrbStack\Samples\Domain\MemoCache;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoIndex;
use LaravelOrbStack\Samples\Domain\MemoNotFound;
use LaravelOrbStack\Samples\Domain\MemoRepository;

final readonly class DeleteMemo
{
    public function __construct(
        private MemoRepository $memos,
        private MemoIndex $index,
        private MemoCache $cache,
    ) {
    }

    /**
     * @throws MemoNotFound
     */
    public function __invoke(MemoId $id): void
    {
        if (! $this->memos->find($id) instanceof Memo) {
            throw MemoNotFound::id($id);
        }

        $this->memos->delete($id);
        $this->index->remove($id);
        $this->cache->forget($id);
    }
}
