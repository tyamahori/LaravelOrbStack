<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\UseCase;

use LaravelOrbStack\Samples\Domain\Memo;
use LaravelOrbStack\Samples\Domain\MemoCache;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoIndex;
use LaravelOrbStack\Samples\Domain\MemoNotFound;
use LaravelOrbStack\Samples\Domain\MemoRepository;

/**
 * Reads the repository, not the cache: the cache may hold a memo whose
 * document was already deleted.
 */
final readonly class EditMemo
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
    public function __invoke(MemoId $id, string $title, string $body): Memo
    {
        $memo = ($this->memos->find($id) ?? throw MemoNotFound::id($id))->edit($title, $body);

        $this->memos->save($memo);
        $this->index->put($memo);
        $this->cache->remember($memo);

        return $memo;
    }
}
