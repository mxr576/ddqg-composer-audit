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

namespace mxr576\ddqgComposerAudit\Application\PackageFinder\PackageIgnore;

use Composer\Semver\Constraint\ConstraintInterface;

/**
 * @internal
 */
interface PackageIgnoreRule
{
    /**
     * Check whether the given rule applies to the given package and version.
     */
    public function evaluate(string $packageName, ConstraintInterface $version): bool;

    public function getPackageName(): string;
}
