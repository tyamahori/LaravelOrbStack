<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Persistence;

use Illuminate\Contracts\Cache\Repository;
use LaravelOrbStack\Samples\Domain\Memo;
use LaravelOrbStack\Samples\Domain\MemoCache;
use LaravelOrbStack\Samples\Domain\MemoId;
use Override;

/**
 * Stores the Memo object itself in the default cache store (Redis here);
 * the store serializes it.
 */
final readonly class IlluminateMemoCache implements MemoCache
{
    private const int TTL_SECONDS = 3600;

    public function __construct(
        private Repository $cache,
    ) {
    }

    #[Override]
    public function remember(Memo $memo): void
    {
        $this->cache->put($this->key($memo->id), $memo, self::TTL_SECONDS);
    }

    #[Override]
    public function get(MemoId $id): ?Memo
    {
        $memo = $this->cache->get($this->key($id));

        return $memo instanceof Memo ? $memo : null;
    }

    private function key(MemoId $id): string
    {
        return 'samples.memo.' . $id->value;
    }
}
