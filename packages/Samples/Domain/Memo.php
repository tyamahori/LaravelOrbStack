<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

use function sprintf;

final readonly class Memo
{
    public const int TITLE_MAX_LENGTH = 100;

    public function __construct(
        public MemoId $id,
        public string $title,
        public string $body,
        public DateTimeImmutable $publishedAt,
    ) {
        if (trim($title) === '' || mb_strlen($title) > self::TITLE_MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf('見出しは 1〜%d 文字です', self::TITLE_MAX_LENGTH));
        }

        if (trim($body) === '') {
            throw new InvalidArgumentException('本文が空です');
        }
    }

    /**
     * Same id and publish time; only the content changes.
     */
    public function edit(string $title, string $body): self
    {
        return new self($this->id, $title, $body, $this->publishedAt);
    }

    public function heading(): MemoHeading
    {
        return new MemoHeading($this->id, $this->title, $this->publishedAt);
    }
}
