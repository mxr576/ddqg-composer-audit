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

use Composer\Package\PackageInterface;
use Composer\Semver\Constraint\ConstraintInterface;
use mxr576\ddqgComposerAudit\Domain\PackageMetadata\PackageMetadataLookup;

/**
 * @internal
 */
final readonly class IgnorePackageByPresentVersionOfOtherPackage implements PackageIgnoreRule
{
    public function __construct(
        private PackageMetadataLookup $packageMetadataLookup,
        private string $packageName,
        private string $otherPackageName,
        private ConstraintInterface $otherPackageRule,
    ) {
    }

    public function evaluate(string $packageName, ConstraintInterface $version): bool
    {
        if ($packageName === $this->packageName) {
            $other_package = $this->packageMetadataLookup->find($this->otherPackageName, $this->otherPackageRule);

            return !$other_package instanceof PackageInterface;
        }

        return false;
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }
}
