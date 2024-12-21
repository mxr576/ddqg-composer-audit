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

use Composer\Package\Locker;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\InstalledRepository;
use Composer\Repository\LockArrayRepository;
use Composer\Repository\RepositoryUtils;
use Composer\Semver\Constraint\ConstraintInterface;
use mxr576\ddqgComposerAudit\Domain\InstalledPackages\InstalledPackagesReadOnlyRepository as InstalledPackagesReadOnlyRepositoryConstract;

/**
 * @internal
 */
final class InstalledPackagesReadOnlyRepository implements InstalledPackagesReadOnlyRepositoryConstract
{
    /**
     * @var callable(string, string|ConstraintInterface): ?PackageInterface
     */
    private $packageFinder;

    /**
     * @var callable(): PackageInterface[]
     */
    private $packageGetter;

    /**
     * Constructs a new object.
     *
     * @param callable(string $package_name, string|ConstraintInterface $version): ?PackageInterface $packageFinder
     * @param callable(): PackageInterface[] $packageGetter
     */
    private function __construct(
        $packageFinder,
        $packageGetter,
    ) {
        $this->packageGetter = $packageGetter;
        $this->packageFinder = $packageFinder;
    }

    public static function fromLocker(Locker $locker, bool $withDevDependencies): self
    {
        if (!$locker->isLocked()) {
            throw new \InvalidArgumentException('Valid composer.json and composer.lock files are required to run this command with --locked');
        }

        try {
            return self::fromLockedPackages($locker->getLockedRepository($withDevDependencies));
        } catch (\RuntimeException $e) {
            throw new \InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public static function fromLockedPackages(LockArrayRepository $lockArrayRepository): self
    {
        return new self(
            static fn (string $package_name, string|ConstraintInterface $version): ?PackageInterface => $lockArrayRepository->findPackage($package_name, $version),
            /** @return PackageInterface[] */
            static fn (): array => $lockArrayRepository->getPackages(),
        );
    }

    public static function fromInstalledPackages(InstalledRepository $installedRepository): self
    {
        return new self(
            static fn (string $package_name, string|ConstraintInterface $version): ?PackageInterface => $installedRepository->findPackage($package_name, $version),
            /** @return PackageInterface[] */
            static fn (): array => $installedRepository->getPackages(),
        );
    }

    public static function fromInstalledRequiredPackages(InstalledRepository $installedRepository, RootPackageInterface $rootPackage): self
    {
        return new self(
            static function (string $package_name, string|ConstraintInterface $version) use (
                $installedRepository, $rootPackage
            ): ?PackageInterface {
                $package = $installedRepository->findPackage($package_name, $version);
                if (null === $package) {
                    return null;
                }

                $tmp = RepositoryUtils::filterRequiredPackages([$package], $rootPackage);
                if ([] === $tmp) {
                    return null;
                }

                return $package;
            },
            /** @return PackageInterface[] */
            static fn (): array => $installedRepository->getPackages(),
        );
    }

    public function findByName(string $package_name, string|ConstraintInterface $version): ?PackageInterface
    {
        return ($this->packageFinder)($package_name, $version);
    }

    public function getPackages(): array
    {
        return ($this->packageGetter)();
    }
}
