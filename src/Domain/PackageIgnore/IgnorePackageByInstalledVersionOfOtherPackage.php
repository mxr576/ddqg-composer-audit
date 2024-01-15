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

namespace mxr576\ddqgComposerAudit\Domain\PackageIgnore;

use Composer\Semver\Constraint\ConstraintInterface;
use mxr576\ddqgComposerAudit\Domain\InstalledPackages\InstalledPackagesReadOnlyRepository;

/**
 * @internal
 */
final class IgnorePackageByInstalledVersionOfOtherPackage implements PackageIgnoreRule
{
    public function __construct(
        private readonly InstalledPackagesReadOnlyRepository $installedPackagesReadOnlyRepository,
        private readonly string $packageName,
        private readonly string $otherPackageName,
        private readonly ConstraintInterface $otherPackageRule
    ) {
    }

    public function evaluate(string $packageName, ConstraintInterface $version): bool
    {
        if ($packageName === $this->packageName) {
            $other_package = $this->installedPackagesReadOnlyRepository->findByName($this->otherPackageName, $this->otherPackageRule);

            return null === $other_package;
        }

        return false;
    }

    public function getPackageName(): string
    {
        return $this->packageName;
    }
}
