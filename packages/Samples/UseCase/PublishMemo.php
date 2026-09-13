<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\UseCase;

use LaravelOrbStack\Samples\Domain\Memo;
use LaravelOrbStack\Samples\Domain\MemoCache;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoRepository;
use Psr\Clock\ClockInterface;

final readonly class PublishMemo
{
    public function __construct(
        private ClockInterface $clock,
        private MemoRepository $memos,
        private MemoCache $cache,
    ) {
    }

    public function __invoke(string $title, string $body): Memo
    {
        $now = $this->clock->now();
        $memo = new Memo(MemoId::at($now), $title, $body, $now);

        $this->memos->save($memo);
        $this->cache->remember($memo);

        return $memo;
    }
}
