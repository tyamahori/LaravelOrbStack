<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Filesystem\Factory as Disks;
use Illuminate\Contracts\Routing\UrlGenerator;
use LaravelOrbStack\Samples\Domain\Memo;
use LaravelOrbStack\Samples\Persistence\MemoRecord;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Tests\TestCase;

/**
 * Runs against the compose stack (RustFS + Redis + PostgreSQL). The fixed
 * clock makes every run overwrite the same S3 key and DB row instead of
 * piling up objects.
 */
final class MemoControllerTest extends TestCase
{
    private const string ID = '20260913-061530-123456';

    private const string OBJECT = 'memos/' . self::ID . '.json';

    private const string CACHE_KEY = 'samples.memo.' . self::ID;

    private UrlGenerator $url;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(ClockInterface::class, new MockClock('2026-09-13 06:15:30.123456'));
        $this->url = $this->app->make(UrlGenerator::class);
        $this->app->make(Disks::class)->disk('s3')->delete(self::OBJECT);
        $this->app->make(Cache::class)->forget(self::CACHE_KEY);
        MemoRecord::query()->whereKey(self::ID)->delete();
    }

    #[Test]
    public function 公開するとS3と索引とキャッシュに保存されセッションが最後のIDを覚える(): void
    {
        $response = $this->post($this->url->route('memos.store'), ['title' => '見出し', 'body' => "本文\n2 行目"]);

        $response->assertRedirect($this->url->route('memos.show', ['id' => self::ID]));
        $response->assertSessionHas('samples.last_published_memo_id', self::ID);
        self::assertTrue($this->app->make(Disks::class)->disk('s3')->exists(self::OBJECT));
        self::assertInstanceOf(Memo::class, $this->app->make(Cache::class)->get(self::CACHE_KEY));
        self::assertSame('見出し', MemoRecord::query()->findOrFail(self::ID)->title);

        $this->get($this->url->route('memos.index'))->assertOk()->assertSeeInOrder([self::ID, '見出し']);
        $this->get($this->url->route('memos.show', ['id' => self::ID]))->assertOk()->assertSee('2 行目');
    }

    #[Test]
    public function キャッシュにある間はS3から消えても表示でき消えると404になる(): void
    {
        $this->post($this->url->route('memos.store'), ['title' => '見出し', 'body' => '本文']);
        $show = $this->url->route('memos.show', ['id' => self::ID]);

        $this->app->make(Disks::class)->disk('s3')->delete(self::OBJECT);
        $this->get($show)->assertOk();

        $this->app->make(Cache::class)->forget(self::CACHE_KEY);
        $this->get($show)->assertNotFound();
    }

    #[Test]
    public function 見出しが空なら公開されず入力エラーが返る(): void
    {
        $response = $this->post($this->url->route('memos.store'), ['title' => '', 'body' => '本文']);

        $response->assertSessionHasErrors('title');
        self::assertFalse($this->app->make(Disks::class)->disk('s3')->exists(self::OBJECT));
    }

    #[Test]
    public function 形式外のIDは経路に一致せず404になる(): void
    {
        $this->get('/memos/not-an-id')->assertNotFound();
    }
}
