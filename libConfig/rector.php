<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        'packages',
        'config',
        'database',
        'public',
        'tests',
    ])
    ->withSkip([
        'vendor',
    ])
    ->withCache(cacheDirectory: './.tempCache/.rector')
    // No version argument: the target is read from composer.json `require.php`.
    ->withPhpSets()
    // The early-return and instanceof rules now live in codeQuality.
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
    )
    // Rector 2.6 removed PHPUnitSetList::PHPUNIT_110; the composer-based set
    // applies version-appropriate PHPUnit sets based on the installed package.
    ->withComposerBased(phpunit: true)
    ->withAttributesSets(phpunit: true)
    // SetList::STRICT_BOOLEANS was removed in Rector 2.6 along with its only
    // rule (DisallowedEmptyRuleFixerRector), which is deprecated upstream.
    ->withImportNames()
    ->withParallel();
