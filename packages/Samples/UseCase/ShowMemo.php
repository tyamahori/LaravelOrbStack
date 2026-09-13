<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\UseCase;

use LaravelOrbStack\Samples\Domain\Memo;
use LaravelOrbStack\Samples\Domain\MemoCache;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoNotFound;
use LaravelOrbStack\Samples\Domain\MemoRepository;

/**
 * Cache-aside read: cache hit returns at once; a miss reads the repository
 * and primes the cache.
 */
final readonly class ShowMemo
{
    public function __construct(
        private MemoRepository $memos,
        private MemoCache $cache,
    ) {
    }

    /**
     * @throws MemoNotFound
     */
    public function __invoke(MemoId $id): Memo
    {
        $cached = $this->cache->get($id);
        if ($cached instanceof Memo) {
            return $cached;
        }

        $memo = $this->memos->find($id) ?? throw MemoNotFound::id($id);
        $this->cache->remember($memo);

        return $memo;
    }
}
