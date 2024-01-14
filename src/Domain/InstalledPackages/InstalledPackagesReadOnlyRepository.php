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

namespace mxr576\ddqgComposerAudit\Domain\InstalledPackages;

use Composer\Package\PackageInterface;
use Composer\Semver\Constraint\ConstraintInterface;

interface InstalledPackagesReadOnlyRepository
{
    public function findByName(string $package_name, string|ConstraintInterface $version): ?PackageInterface;

    /**
     * @return \Composer\Package\PackageInterface[]
     */
    public function getPackages(): array;
}
