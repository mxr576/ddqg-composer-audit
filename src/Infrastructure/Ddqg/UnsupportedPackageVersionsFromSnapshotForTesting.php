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

namespace mxr576\ddqgComposerAudit\Infrastructure\Ddqg;

use JsonMachine\FileChunks;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\UnsupportedPackageVersionsProvider;

/**
 * @internal
 */
final class UnsupportedPackageVersionsFromSnapshotForTesting implements UnsupportedPackageVersionsProvider
{
    public function findByPackages(string ...$package_names): array
    {
        return PackageVersionConstraintsFromDdqgComposerJson::extract(new FileChunks(__DIR__ . '/fixtures/unsupported-composer-2023-04.29.json'), $package_names);
    }
}
