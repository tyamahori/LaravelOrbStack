<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use LaravelOrbStack\Samples\Domain\MemoHeading;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoNotFound;
use LaravelOrbStack\Samples\UseCase\DeleteMemo;
use LaravelOrbStack\Samples\UseCase\EditMemo;
use LaravelOrbStack\Samples\UseCase\PublishMemo;
use LaravelOrbStack\Samples\UseCase\ShowMemo;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class MemoLifecycleTest extends TestCase
{
    #[Test]
    public function 編集はIDと公開日時を保ったまま本文と索引とキャッシュを書き換える(): void
    {
        $repository = new FakeMemoStore();
        $index = new FakeMemoStore();
        $cache = new FakeMemoStore();
        $memo = (new PublishMemo(new MockClock('2026-09-13 06:15:30'), $repository, $index, $cache))('前', '前の本文');

        $edited = (new EditMemo($repository, $index, $cache))($memo->id, '後', '後の本文');

        self::assertSame($memo->id, $edited->id);
        self::assertSame($memo->publishedAt, $edited->publishedAt);
        self::assertSame('後の本文', $repository->find($memo->id)?->body);
        self::assertSame(['後'], array_map(static fn (MemoHeading $heading): string => $heading->title, $index->latest()));
        self::assertSame($edited, $cache->get($memo->id));
    }

    #[Test]
    public function 編集はキャッシュにしか残っていないメモを見つからない扱いにする(): void
    {
        $cache = new FakeMemoStore();
        $memo = (new PublishMemo(new MockClock(), new FakeMemoStore(), new FakeMemoStore(), $cache))('見出し', '本文');

        $this->expectException(MemoNotFound::class);

        (new EditMemo(new FakeMemoStore(), new FakeMemoStore(), $cache))($memo->id, '後', '後の本文');
    }

    #[Test]
    public function 削除すると保存先と索引とキャッシュから消え表示できなくなる(): void
    {
        $store = new FakeMemoStore();
        $memo = (new PublishMemo(new MockClock(), $store, $store, $store))('見出し', '本文');

        (new DeleteMemo($store, $store, $store))($memo->id);

        self::assertSame([], $store->latest());
        $this->expectException(MemoNotFound::class);
        (new ShowMemo($store, $store))($memo->id);
    }

    #[Test]
    public function 存在しないメモの削除は見つからない例外になる(): void
    {
        $this->expectException(MemoNotFound::class);

        (new DeleteMemo(new FakeMemoStore(), new FakeMemoStore(), new FakeMemoStore()))(new MemoId('20260913-061530-000001'));
    }
}
