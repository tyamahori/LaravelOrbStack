<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use DateTimeImmutable;
use InvalidArgumentException;
use LaravelOrbStack\Samples\Domain\Memo;
use LaravelOrbStack\Samples\Domain\MemoId;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class MemoTest extends TestCase
{
    #[Test]
    public function iDは時刻から導かれ文字列順が時刻順になる(): void
    {
        $earlier = MemoId::at(new DateTimeImmutable('2026-09-13 06:15:30.000001'));
        $later = MemoId::at(new DateTimeImmutable('2026-09-13 06:15:30.000002'));

        self::assertSame('20260913-061530-000001', $earlier->value);
        self::assertLessThan($later->value, $earlier->value);
    }

    #[Test]
    public function パス区切りを含むIDは拒否される(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MemoId('../20260913-061530-000001');
    }

    #[Test]
    public function 空白だけの見出しは拒否される(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Memo(new MemoId('20260913-061530-000001'), '  ', '本文', new DateTimeImmutable());
    }

    #[Test]
    public function 見出しは上限の文字数までは受け付ける(): void
    {
        $title = str_repeat('あ', Memo::TITLE_MAX_LENGTH);
        $memo = new Memo(new MemoId('20260913-061530-000001'), $title, '本文', new DateTimeImmutable());

        self::assertSame($title, $memo->title);

        $this->expectException(InvalidArgumentException::class);
        new Memo(new MemoId('20260913-061530-000001'), $title . 'あ', '本文', new DateTimeImmutable());
    }
}
