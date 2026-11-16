<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023-2026 Dezső Biczó
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/mxr576/ddqg-composer-audit/LICENSE.md
 *
 */

use Rector\Config\RectorConfig;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/phparkitect.php',
        __DIR__ . '/.php-cs-fixer.dist.php',
        __DIR__ . '/rector.php',
    ])
    ->withSkip([
        __DIR__ . '/tests/fixtures/e2e/vendor',
        __DIR__ . '/tests/fixtures/e2e/web',
        __DIR__ . '/tests/fixtures/composer_home',
        // The repo calls PHPUnit assertions as self::assert*(); php-cs-fixer enforces it
        // (php_unit_test_case_static_method_calls).
        PreferPHPUnitThisCallRector::class,
    ])
    // Must be a bootstrap file, not an autoload path: Rector has to *execute* the project
    // autoloader to resolve vendor symbols (e.g. Composer's PluginInterface). Without it, runs
    // that do not start from the repo root can make RemoveEmptyClassMethodRector delete
    // Plugin::deactivate()/uninstall().
    ->withBootstrapFiles([__DIR__ . '/vendor/autoload.php'])
    ->withPhpSets()
    ->withComposerBased(phpunit: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        instanceOf: true,
        earlyReturn: true,
        phpunitCodeQuality: true,
    );
