<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\CodeQuality\General\ExtEmConfRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/../../Classes/',
        __DIR__ . '/../../Configuration/',
        __DIR__ . '/../../Tests/',
        __DIR__ . '/../../ext_emconf.php',
        __DIR__ . '/../../ext_localconf.php',
    ])
    ->withPhpVersion(PhpVersion::PHP_83)
    ->withPhpSets(true)
    ->withSets([
        PHPUnitSetList::PHPUNIT_100,
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_13,
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
        ExplicitBoolCompareRector::class,
    ])
    ->withSkip([
        __DIR__ . '/../../.Build',
    ])
    ->withConfiguredRule(
        ExtEmConfRector::class,
        [
            ExtEmConfRector::PHP_VERSION_CONSTRAINT => '8.3.0-8.99.99',
            ExtEmConfRector::TYPO3_VERSION_CONSTRAINT => '13.4.0-13.99.99',
        ]
    )
    ->withImportNames(importShortClasses: false, removeUnusedImports: true);
