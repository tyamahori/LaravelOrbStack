<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Domain;

use RuntimeException;

final class MemoNotFound extends RuntimeException
{
    public static function id(MemoId $id): self
    {
        return new self("メモが見つかりません: {$id->value}");
    }
}
