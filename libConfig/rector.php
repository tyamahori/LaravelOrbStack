<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->cacheDirectory('./.tempCache/.rector');
    $rectorConfig->skip([
        'vendor',
        'routes',
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
    ]);
    $rectorConfig->cacheClass(FileCacheStorage::class);
    $rectorConfig->sets([
        SetList::PHP_84,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::INSTANCEOF,
        SetList::STRICT_BOOLEANS,
        PHPUnitSetList::PHPUNIT_110,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
    $rectorConfig->importNames();
    $rectorConfig->parallel();
};
