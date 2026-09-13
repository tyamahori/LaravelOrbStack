<?php

declare(strict_types=1);

namespace LaravelOrbStack\Samples\Persistence;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * Row of `memos` (database/schema.sql). Larastan cannot read the DDL, so
 * the columns are declared here.
 *
 * @property string $id
 * @property string $title
 * @property CarbonImmutable $published_at
 */
final class MemoRecord extends Model
{
    #[Override]
    public $incrementing = false;

    #[Override]
    public $timestamps = false;

    #[Override]
    protected $table = 'memos';

    #[Override]
    protected $keyType = 'string';

    /**
     * Microseconds match the id, which is derived from the same instant.
     */
    #[Override]
    protected $dateFormat = 'Y-m-d H:i:s.u';

    /**
     * @inheritdoc
     */
    #[Override]
    protected $fillable = [
        'id',
        'title',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
