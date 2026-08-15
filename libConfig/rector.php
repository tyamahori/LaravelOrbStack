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
        SetList::PHP_85,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::INSTANCEOF,
        // Rector 2.6 removed PHPUnitSetList::PHPUNIT_110; COMPOSER_BASED applies
        // version-appropriate PHPUnit sets based on the installed package.
        PHPUnitSetList::COMPOSER_BASED,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
    // SetList::STRICT_BOOLEANS was removed in Rector 2.6 along with its only
    // rule (DisallowedEmptyRuleFixerRector), which is deprecated upstream.
    $rectorConfig->importNames();
    $rectorConfig->parallel();
};
