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
    public function 公開したメモはリポジトリとキャッシュの両方に入る(): void
    {
        $repository = new InMemoryMemoStore();
        $cache = new InMemoryMemoStore();
        $clock = new MockClock('2026-09-13 06:15:30.123456');

        $memo = (new PublishMemo($clock, $repository, $cache))('見出し', '本文');

        self::assertSame('20260913-061530-123456', $memo->id->value);
        self::assertSame($memo, $repository->find($memo->id));
        self::assertSame($memo, $cache->get($memo->id));
    }

    #[Test]
    public function キャッシュにないメモはリポジトリから読んでキャッシュに入れる(): void
    {
        $repository = new InMemoryMemoStore();
        $cache = new InMemoryMemoStore();
        $memo = (new PublishMemo(new MockClock(), $repository, new InMemoryMemoStore()))('見出し', '本文');

        $shown = (new ShowMemo($repository, $cache))($memo->id);

        self::assertSame($memo, $shown);
        self::assertSame($memo, $cache->get($memo->id));
    }

    #[Test]
    public function キャッシュにあればリポジトリを読まない(): void
    {
        $cache = new InMemoryMemoStore();
        $memo = (new PublishMemo(new MockClock(), new InMemoryMemoStore(), $cache))('見出し', '本文');

        $shown = (new ShowMemo(new InMemoryMemoStore(), $cache))($memo->id);

        self::assertSame($memo, $shown);
    }

    #[Test]
    public function どこにもないメモはMemoNotFoundになる(): void
    {
        $this->expectException(MemoNotFound::class);

        (new ShowMemo(new InMemoryMemoStore(), new InMemoryMemoStore()))(new MemoId('20260913-061530-000001'));
    }
}
