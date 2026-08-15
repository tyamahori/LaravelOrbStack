<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;

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
        PHPUnitSetList::PHPUNIT_110,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
    // Rector 2.6 removed SetList::STRICT_BOOLEANS; register its sole rule directly.
    $rectorConfig->rules([
        DisallowedEmptyRuleFixerRector::class,
    ]);
    $rectorConfig->importNames();
    $rectorConfig->parallel();
};
