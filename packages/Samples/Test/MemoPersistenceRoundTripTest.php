<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use DateTimeImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Filesystem\Factory as Disks;
use LaravelOrbStack\Samples\Domain\Memo;
use LaravelOrbStack\Samples\Domain\MemoCache;
use LaravelOrbStack\Samples\Domain\MemoHeading;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoIndex;
use LaravelOrbStack\Samples\Domain\MemoRepository;
use LaravelOrbStack\Samples\Persistence\MemoRecord;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Writes to the real stores and reads back. Fakes cannot catch format drift
 * (the microsecond loss in the S3 JSON only showed up here), so each
 * Persistence implementation gets one round trip against the compose stack.
 */
final class MemoPersistenceRoundTripTest extends TestCase
{
    private const string ID = '20260913-061532-000007';

    private Memo $memo;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->make(Disks::class)->disk('s3')->delete('memos/' . self::ID . '.json');
        $this->app->make(Cache::class)->forget('samples.memo.' . self::ID);
        MemoRecord::query()->whereKey(self::ID)->delete();
        $this->memo = new Memo(new MemoId(self::ID), '見出し', "本文\n2 行目", new DateTimeImmutable('2026-09-13 06:15:32.000007'));
    }

    #[Test]
    public function s3の往復でメモが等しい(): void
    {
        $repository = $this->app->make(MemoRepository::class);
        $repository->save($this->memo);

        self::assertEquals($this->memo, $repository->find($this->memo->id));
    }

    #[Test]
    public function postgreSQLの往復で見出しが等しい(): void
    {
        $index = $this->app->make(MemoIndex::class);
        $index->put($this->memo);

        $found = array_values(array_filter($index->latest(), static fn (MemoHeading $heading): bool => $heading->id->value === self::ID));
        self::assertEquals([$this->memo->heading()], $found);
    }

    #[Test]
    public function redisの往復でメモが等しい(): void
    {
        $cache = $this->app->make(MemoCache::class);
        $cache->remember($this->memo);

        self::assertEquals($this->memo, $cache->get($this->memo->id));
    }
}
