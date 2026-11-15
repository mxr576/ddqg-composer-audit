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

namespace mxr576\ddqgComposerAudit\Domain\PackageIgnore;

use Composer\Semver\Constraint\Constraint;
use mxr576\ddqgComposerAudit\Domain\PackageMetadata\PackageMetadataLookup;

/**
 * VariationCache moved into core in 10.2.0, so it is only reported as
 * deprecated for projects that have such a core.
 *
 * This is a sufficient implementation for the one and only known edge case.
 *
 * @see https://github.com/mxr576/ddqg-composer-audit/issues/18
 *
 * @internal
 */
final class IgnoreVariationcacheMovedIntoCore implements PackageIgnoreRuleProvider
{
    public function __construct(private readonly PackageMetadataLookup $packageMetadataLookup)
    {
    }

    public function getIgnoreRules(): iterable
    {
        yield new IgnorePackageByPresentVersionOfOtherPackage(
            $this->packageMetadataLookup,
            'drupal/variationcache',
            'drupal/core',
            new Constraint(Constraint::STR_OP_GE, '10.2.0'),
        );
    }
}
