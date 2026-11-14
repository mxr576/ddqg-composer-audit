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

namespace mxr576\ddqgComposerAudit\Infrastructure\Composer;

use Composer\Package\Locker;
use Composer\Package\PackageInterface;
use Composer\Repository\LockArrayRepository;
use Composer\Repository\RepositoryInterface;
use Composer\Semver\Constraint\ConstraintInterface;
use mxr576\ddqgComposerAudit\Domain\PackageMetadata\PackageMetadataLookup;

/**
 * Looks up package metadata in the installed repository first, then in the
 * locked repository (if there is a lock file).
 *
 * @internal
 */
final class PackageMetadataLookupFromComposerRepositories implements PackageMetadataLookup
{
    /**
     * @var \Closure(): ?LockArrayRepository
     */
    private readonly \Closure $lockedRepositoryProvider;

    private bool $lockedRepositoryLoaded = false;

    private ?LockArrayRepository $lockedRepository = null;

    /**
     * @param \Closure(): ?LockArrayRepository $lockedRepositoryProvider
     *   Returns the locked repository, or null if there is no lock file. It is
     *   called at most once, and only when the installed repository has no
     *   match.
     */
    public function __construct(
        private readonly RepositoryInterface $installedRepository,
        \Closure $lockedRepositoryProvider,
    ) {
        $this->lockedRepositoryProvider = $lockedRepositoryProvider;
    }

    public static function fromInstalledRepositoryAndLocker(RepositoryInterface $installedRepository, Locker $locker): self
    {
        return new self($installedRepository, static function () use ($locker): ?LockArrayRepository {
            // A missing lock file is not an error: there is just no locked
            // metadata to find.
            if (!$locker->isLocked()) {
                return null;
            }

            try {
                return $locker->getLockedRepository(true);
            } catch (\RuntimeException) {
                // The lock file has no require-dev information (it was
                // written with --no-dev), so only required packages are there.
                try {
                    return $locker->getLockedRepository(false);
                } catch (\RuntimeException) {
                    // Composer reports an unreadable lock file itself; the
                    // lookup gives no metadata rather than breaking the audit.
                    return null;
                }
            }
        });
    }

    public function find(string $packageName, string|ConstraintInterface $version): ?PackageInterface
    {
        $package = $this->installedRepository->findPackage($packageName, $version);
        if (null !== $package) {
            return $package;
        }

        if (!$this->lockedRepositoryLoaded) {
            $this->lockedRepository = ($this->lockedRepositoryProvider)();
            $this->lockedRepositoryLoaded = true;
        }

        return $this->lockedRepository?->findPackage($packageName, $version);
    }
}
