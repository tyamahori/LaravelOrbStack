<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Test;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Filesystem\Factory as Disks;
use Illuminate\Contracts\Routing\UrlGenerator;
use LaravelOrbStack\Samples\Persistence\MemoRecord;
use Override;
use PHPUnit\Framework\Attributes\Test;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;
use Tests\TestCase;

/**
 * Runs against the compose stack like MemoControllerTest. Uses a different
 * fixed clock so the two suites never share an S3 key or DB row.
 */
final class MemoApiTest extends TestCase
{
    private const string ID = '20260913-071530-123456';

    private const string OBJECT = 'memos/' . self::ID . '.json';

    private const string CACHE_KEY = 'samples.memo.' . self::ID;

    private UrlGenerator $url;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(ClockInterface::class, new MockClock('2026-09-13 07:15:30.123456'));
        $this->url = $this->app->make(UrlGenerator::class);
        $this->app->make(Disks::class)->disk('s3')->delete(self::OBJECT);
        $this->app->make(Cache::class)->forget(self::CACHE_KEY);
        MemoRecord::query()->whereKey(self::ID)->delete();
    }

    #[Test]
    public function 公開すると201とLocationが返り一覧と表示がJSONで読める(): void
    {
        $response = $this->postJson($this->url->route('api.memos.store'), ['title' => '見出し', 'body' => "本文\n2 行目"]);

        $response->assertCreated()
            ->assertHeader('Location', $this->url->route('api.memos.show', ['id' => self::ID]))
            ->assertExactJson([
                'id' => self::ID,
                'title' => '見出し',
                'body' => "本文\n2 行目",
                'published_at' => '2026-09-13T07:15:30.123456+00:00',
            ]);

        $this->getJson($this->url->route('api.memos.show', ['id' => self::ID]))
            ->assertOk()
            ->assertJsonPath('body', "本文\n2 行目");
        $this->getJson($this->url->route('api.memos.index'))
            ->assertOk()
            ->assertJsonFragment(['id' => self::ID, 'title' => '見出し'])
            ->assertJsonMissingPath('0.body');
    }

    #[Test]
    public function 更新と削除が往復しIDと公開日時は変わらない(): void
    {
        $this->postJson($this->url->route('api.memos.store'), ['title' => '前', 'body' => '前の本文']);

        $this->putJson($this->url->route('api.memos.update', ['id' => self::ID]), ['title' => '後', 'body' => '後の本文'])
            ->assertOk()
            ->assertJsonPath('title', '後')
            ->assertJsonPath('published_at', '2026-09-13T07:15:30.123456+00:00');

        $this->deleteJson($this->url->route('api.memos.destroy', ['id' => self::ID]))->assertNoContent();
        self::assertFalse($this->app->make(Disks::class)->disk('s3')->exists(self::OBJECT));
        self::assertNull(MemoRecord::query()->find(self::ID));
        $this->getJson($this->url->route('api.memos.show', ['id' => self::ID]))->assertNotFound()->assertJsonStructure(['message']);
    }

    #[Test]
    public function acceptヘッダがなくても入力エラーは422のJSONで返る(): void
    {
        $response = $this->post($this->url->route('api.memos.store'), ['title' => '', 'body' => '本文']);

        $response->assertUnprocessable()->assertJsonValidationErrors('title');
        self::assertFalse($this->app->make(Disks::class)->disk('s3')->exists(self::OBJECT));
    }
}
