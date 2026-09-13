<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoNotFound;
use LaravelOrbStack\Samples\UseCase\PublishMemo;
use LaravelOrbStack\Samples\UseCase\ShowMemo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class ShowMemoTest extends TestCase
{
    #[Test]
    public function 公開したメモはリポジトリと索引とキャッシュに入る(): void
    {
        $repository = new FakeMemoStore();
        $index = new FakeMemoStore();
        $cache = new FakeMemoStore();
        $clock = new MockClock('2026-09-13 06:15:30.123456');

        $memo = (new PublishMemo($clock, $repository, $index, $cache))('見出し', '本文');

        self::assertSame('20260913-061530-123456', $memo->id->value);
        self::assertSame($memo, $repository->find($memo->id));
        self::assertEquals([$memo->heading()], $index->latest());
        self::assertSame($memo, $cache->get($memo->id));
    }

    #[Test]
    public function 索引は新しい順に並ぶ(): void
    {
        $index = new FakeMemoStore();
        $store = new FakeMemoStore();
        $clock = new MockClock('2026-09-13 06:15:30');
        $publish = new PublishMemo($clock, $store, $index, $store);

        $first = $publish('先', '本文');
        $clock->sleep(1);
        $second = $publish('後', '本文');

        self::assertEquals([$second->heading(), $first->heading()], $index->latest());
    }

    #[Test]
    public function キャッシュにないメモはリポジトリから読んでキャッシュに入れる(): void
    {
        $repository = new FakeMemoStore();
        $cache = new FakeMemoStore();
        $memo = (new PublishMemo(new MockClock(), $repository, new FakeMemoStore(), new FakeMemoStore()))('見出し', '本文');

        $shown = (new ShowMemo($repository, $cache))($memo->id);

        self::assertSame($memo, $shown);
        self::assertSame($memo, $cache->get($memo->id));
    }

    #[Test]
    public function キャッシュにあればリポジトリを読まない(): void
    {
        $cache = new FakeMemoStore();
        $memo = (new PublishMemo(new MockClock(), new FakeMemoStore(), new FakeMemoStore(), $cache))('見出し', '本文');

        $shown = (new ShowMemo(new FakeMemoStore(), $cache))($memo->id);

        self::assertSame($memo, $shown);
    }

    #[Test]
    public function どこにもないメモはMemoNotFoundになる(): void
    {
        $this->expectException(MemoNotFound::class);

        (new ShowMemo(new FakeMemoStore(), new FakeMemoStore()))(new MemoId('20260913-061530-000001'));
    }
}
