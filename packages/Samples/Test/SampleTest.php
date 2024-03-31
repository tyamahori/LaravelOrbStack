<?php
declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use LaravelOrbStack\Samples\Sample;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase
{
    #[Override]
    public static function setUpBeforeClass(): void
    {
        // Do nothing.
    }

    #[Test]
    public function getterメソッドの結果がコンストラクタ引数と同一(): void
    {
        $sut = new Sample();
        self::assertSame('Sample', $sut->sample());
    }
}
