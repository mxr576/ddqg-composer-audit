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

namespace mxr576\ddqgComposerAudit\Domain\PackageMetadata;

use Composer\Package\PackageInterface;
use Composer\Semver\Constraint\ConstraintInterface;

interface PackageMetadataLookup
{
    /**
     * Finds the metadata of a package version, or null if there is none.
     */
    public function find(string $packageName, string|ConstraintInterface $version): ?PackageInterface;
}
