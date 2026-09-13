<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Persistence;

use LaravelOrbStack\Samples\Domain\Memo;
use LaravelOrbStack\Samples\Domain\MemoHeading;
use LaravelOrbStack\Samples\Domain\MemoId;
use LaravelOrbStack\Samples\Domain\MemoIndex;
use Override;

final readonly class EloquentMemoIndex implements MemoIndex
{
    #[Override]
    public function add(Memo $memo): void
    {
        MemoRecord::query()->create([
            'id' => $memo->id->value,
            'title' => $memo->title,
            'published_at' => $memo->publishedAt,
        ]);
    }

    /**
     * Ids sort by publish time, so the primary key is the listing order.
     */
    #[Override]
    public function latest(): array
    {
        $headings = [];
        foreach (MemoRecord::query()->latest('id')->get() as $record) {
            $headings[] = new MemoHeading(
                new MemoId($record->id),
                $record->title,
                $record->published_at->toDateTimeImmutable(),
            );
        }

        return $headings;
    }
}
