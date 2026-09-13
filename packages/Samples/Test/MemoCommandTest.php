<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Filesystem\Factory as Disks;
use LaravelOrbStack\Samples\Persistence\MemoRecord;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Tests\TestCase;

/**
 * Runs against the compose stack (RustFS + Redis); see MemoControllerTest.
 * Kernel::output() holds everything the last call wrote; the buffered
 * output has no separate stderr, so diagnostics land there too.
 */
final class MemoCommandTest extends TestCase
{
    private const string ID = '20260913-061531-654321';

    private Kernel $artisan;

    private string $file;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(ClockInterface::class, new MockClock('2026-09-13 06:15:31.654321'));
        $this->app->make(Disks::class)->disk('s3')->delete('memos/' . self::ID . '.json');
        $this->app->make(Cache::class)->forget('samples.memo.' . self::ID);
        MemoRecord::query()->whereKey(self::ID)->delete();
        $this->artisan = $this->app->make(Kernel::class);

        $this->file = (string) tempnam(sys_get_temp_dir(), 'memo');
        file_put_contents($this->file, "本文\n");
    }

    #[Override]
    protected function tearDown(): void
    {
        unlink($this->file);

        parent::tearDown();
    }

    #[Test]
    public function 公開したIDをそのままshowに渡せる(): void
    {
        self::assertSame(Command::SUCCESS, $this->artisan->call('memo:publish', ['file' => $this->file, '--title' => '見出し']));
        $id = $this->artisan->output();
        self::assertSame(self::ID . "\n", $id);

        self::assertSame(Command::SUCCESS, $this->artisan->call('memo:show', ['id' => trim($id)]));
        self::assertSame("見出し\n\n本文\n\n", $this->artisan->output());
    }

    #[Test]
    public function jsonオプションは1行のJSONを出す(): void
    {
        $line = '{"id":"' . self::ID . '","title":"見出し","body":"本文\n","published_at":"2026-09-13T06:15:31.654+00:00"}' . "\n";

        self::assertSame(Command::SUCCESS, $this->artisan->call('memo:publish', ['file' => $this->file, '--title' => '見出し', '--json' => true]));
        self::assertSame($line, $this->artisan->output());

        self::assertSame(Command::SUCCESS, $this->artisan->call('memo:show', ['id' => self::ID, '--json' => true]));
        self::assertSame($line, $this->artisan->output());
    }

    #[Test]
    public function 見出しを省略するとファイル名になる(): void
    {
        self::assertSame(Command::SUCCESS, $this->artisan->call('memo:publish', ['file' => $this->file]));

        $this->artisan->call('memo:show', ['id' => self::ID]);
        self::assertStringStartsWith(pathinfo($this->file, PATHINFO_FILENAME) . "\n", $this->artisan->output());
    }

    #[Test]
    public function 読めないファイルは終了コード2で何も公開しない(): void
    {
        self::assertSame(Command::INVALID, $this->artisan->call('memo:publish', ['file' => '/nonexistent/memo.txt']));
        self::assertFalse($this->app->make(Disks::class)->disk('s3')->exists('memos/' . self::ID . '.json'));
    }

    #[Test]
    public function 存在しないIDは終了コード1になる(): void
    {
        self::assertSame(Command::FAILURE, $this->artisan->call('memo:show', ['id' => self::ID]));
        self::assertSame('メモが見つかりません: ' . self::ID . "\n", $this->artisan->output());
    }

    #[Test]
    public function 編集したメモをshowで読み削除すると見つからなくなる(): void
    {
        $this->artisan->call('memo:publish', ['file' => $this->file, '--title' => '前']);
        file_put_contents($this->file, "後の本文\n");

        self::assertSame(Command::SUCCESS, $this->artisan->call('memo:edit', ['id' => self::ID, 'file' => $this->file, '--title' => '後']));
        self::assertSame(self::ID . "\n", $this->artisan->output());
        $this->artisan->call('memo:show', ['id' => self::ID]);
        self::assertSame("後\n\n後の本文\n\n", $this->artisan->output());

        self::assertSame(Command::SUCCESS, $this->artisan->call('memo:delete', ['id' => self::ID]));
        self::assertSame('', $this->artisan->output());
        self::assertSame(Command::FAILURE, $this->artisan->call('memo:show', ['id' => self::ID]));
        self::assertSame(Command::FAILURE, $this->artisan->call('memo:delete', ['id' => self::ID]));
    }

    #[Test]
    public function 存在しないメモの編集は終了コード1で何も保存しない(): void
    {
        self::assertSame(Command::FAILURE, $this->artisan->call('memo:edit', ['id' => self::ID, 'file' => $this->file]));
        self::assertFalse($this->app->make(Disks::class)->disk('s3')->exists('memos/' . self::ID . '.json'));
    }
}
