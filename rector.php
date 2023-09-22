<?php

declare(strict_types=1);

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->cacheDirectory('./.rector');
    $rectorConfig->skip([
        'vendor'
    ]);
    $rectorConfig->paths([
        'packages',
        'app',
        'config',
        'database',
        'public',
        'resources',
        'tests',
        'routes'
    ]);
    $rectorConfig->cacheClass(FileCacheStorage::class);
    $rectorConfig->rules([
        Rector\PHPUnit\PHPUnit100\Rector\Class_\StaticDataProviderClassMethodRector::class,
        Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector::class,
        Rector\Php74\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector::class,
    ]);
};
