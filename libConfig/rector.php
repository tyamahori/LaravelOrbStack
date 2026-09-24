<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;

return RectorConfig::configure()
    ->withPaths([
        'bootstrap/app.php',
        'libConfig',
        'packages',
        'config',
        'database',
        'public',
        'tests',
    ])
    ->withSkip([
        'vendor',
        // Rector reads jetbrains/phpstorm-stubs, which lacks Xdebug 3.1's $clear.
        RemoveExtraParametersRector::class => ['tests/ForbiddenCallMonitor.php'],
    ])
    ->withCache(cacheDirectory: './.tempCache/.rector')
    // No version argument: the target is read from composer.json `require.php`.
    ->withPhpSets()
    // The early-return and instanceof rules now live in codeQuality.
    // The one version-gated rule withPhpSets() leaves out that Rector has not
    // deprecated; it auto-fixes what PHPStan checkMissingOverrideMethodAttribute
    // reports. Rector 2.6 deprecated the pipe-operator, property-hook,
    // #[Deprecated] and JSON_THROW_ON_ERROR rules as unsafe or preference-only,
    // and has no rule for asymmetric visibility or clone-with.
    ->withRules([AddOverrideAttributeToOverriddenMethodsRector::class])
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
