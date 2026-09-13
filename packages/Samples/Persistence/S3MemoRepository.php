<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Persistence;

use DateTimeImmutable;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use LaravelOrbStack\Samples\Domain\Memo;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoRepository;
use Override;

/**
 * One JSON object per memo at `memos/<id>.json` on the `s3` disk.
 */
final readonly class S3MemoRepository implements MemoRepository
{
    private const string PREFIX = 'memos';

    private Filesystem $disk;

    public function __construct(Factory $disks)
    {
        $this->disk = $disks->disk('s3');
    }

    #[Override]
    public function save(Memo $memo): void
    {
        $this->disk->put($this->path($memo->id), json_encode([
            'id' => $memo->id->value,
            'title' => $memo->title,
            'body' => $memo->body,
            'published_at' => $memo->publishedAt->format(DateTimeImmutable::RFC3339_EXTENDED),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    #[Override]
    public function find(MemoId $id): ?Memo
    {
        $path = $this->path($id);
        if (! $this->disk->exists($path)) {
            return null;
        }

        return $this->decode((string) $this->disk->get($path));
    }

    private function path(MemoId $id): string
    {
        return self::PREFIX . '/' . $id->value . '.json';
    }

    private function decode(string $json): Memo
    {
        /** @var array{id: string, title: string, body: string, published_at: string} $data */
        $data = json_decode($json, true, 8, JSON_THROW_ON_ERROR);

        return new Memo(
            new MemoId($data['id']),
            $data['title'],
            $data['body'],
            new DateTimeImmutable($data['published_at']),
        );
    }
}
