<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->cacheDirectory('./.rector');
    $rectorConfig->skip([
        'vendor',
    ]);
    $rectorConfig->paths([
        'packages',
        'app',
        'config',
        'database',
        'public',
        'resources',
        'tests',
        'routes',
        '*.php',
    ]);
    $rectorConfig->cacheClass(FileCacheStorage::class);
    $rectorConfig->sets([
        SetList::PHP_83,
        SetList::PHP_82,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::INSTANCEOF,
        SetList::STRICT_BOOLEANS,
    ]);
    $rectorConfig->importNames();
    $rectorConfig->parallel();
};
