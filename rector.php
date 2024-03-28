<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->cacheDirectory('./.rector');
    $rectorConfig->skip([
        'vendor',
        '_ide_*.php',
        '.phpstorm.meta.php',
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
        SetList::PHP_83,
        SetList::PHP_82,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::INSTANCEOF,
        SetList::STRICT_BOOLEANS,
        PHPUnitSetList::PHPUNIT_100,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
    $rectorConfig->importNames();
    $rectorConfig->parallel();
};
