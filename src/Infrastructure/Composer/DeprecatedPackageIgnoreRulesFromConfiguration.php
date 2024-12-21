<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023-2024 Dezső Biczó
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/mxr576/ddqg-composer-audit/LICENSE.md
 *
 */

namespace mxr576\ddqgComposerAudit\Infrastructure\Composer;

use mxr576\ddqgComposerAudit\Application\PackageFinder\DeprecatedPackageFinderConfigurationProvider;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRuleProvider;

/**
 * @internal
 */
final class DeprecatedPackageIgnoreRulesFromConfiguration implements PackageIgnoreRuleProvider
{
    public function __construct(
        private readonly DeprecatedPackageFinderConfigurationProvider $configurationProvider,
    ) {
    }

    public function getIgnoreRules(): iterable
    {
        yield from $this->configurationProvider->getDeprecatedPackageIgnoreRules();
    }
}
