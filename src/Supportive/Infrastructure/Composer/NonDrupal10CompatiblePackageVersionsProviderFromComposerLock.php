<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023 Dezső Biczó
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/mxr576/ddqg-composer-audit/LICENSE.md
 *
 */

namespace mxr576\ddqgComposerAudit\Supportive\Infrastructure\Composer;

use Composer\Repository\LockArrayRepository;
use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\NonDrupal10CompatiblePackageVersionsProvider;

/**
 * @internal
 */
final class NonDrupal10CompatiblePackageVersionsProviderFromComposerLock implements NonDrupal10CompatiblePackageVersionsProvider
{
    private const DRUPAL_PACKAGE_TYPES = [
      'drupal-module',
      'drupal-theme',
      'drupal-profile',
      'drupal-custom-module',
      'drupal-custom-theme',
      'drupal-custom-profile',
    ];

    private const DRUPAL_CORE_PACKAGE_CANDIDATES_IN_ORDER = [
      'drupal/core',
      'drupal/core-recommended',
    ];

    private const DRUPAL_CORE_DEV_PACKAGE_CANDIDATES_IN_ORDER = [
      'drupal/core-dev',
    ];

    public function __construct(private readonly LockArrayRepository $lockRepository, private readonly VersionParser $versionParser)
    {
    }

    /**
       * {@inheritDoc}
       */
      public function findByPackages(string ...$package_names): array
      {
          $result = [];
          $d10_compatible_constraint = new Constraint('>=', $this->versionParser->normalize('10.0.0'));
          foreach ($this->lockRepository->getPackages() as $package) {
              if (!in_array($package->getName(), $package_names, true)) {
                  continue;
              }
              if (!in_array($package->getType(), self::DRUPAL_PACKAGE_TYPES, true)) {
                  continue;
              }

              $drupal_core_dep = null;
              foreach (self::DRUPAL_CORE_PACKAGE_CANDIDATES_IN_ORDER as $drupal_core_pkg_name) {
                  if (array_key_exists($drupal_core_pkg_name, $package->getRequires())) {
                      $drupal_core_dep = $package->getRequires()[$drupal_core_pkg_name];
                      break;
                  }
              }
              if (null === $drupal_core_dep) {
                  foreach (self::DRUPAL_CORE_DEV_PACKAGE_CANDIDATES_IN_ORDER as $drupal_core_pkg_name) {
                      if (array_key_exists($drupal_core_pkg_name, $package->getDevRequires())) {
                          $drupal_core_dep = $package->getDevRequires()[$drupal_core_pkg_name];
                          break;
                      }
                  }
              }

              if (null === $drupal_core_dep) {
                  // @todo Consider logging this event.
                  //   For that, we may need a PSR3 compliant logger wrapper
                  //   around Composer IO.
                  continue;
              }

              if (!$d10_compatible_constraint->matches($drupal_core_dep->getConstraint())) {
                  $result[$package->getName()] = $package->getPrettyVersion();
              }
          }

          return $result;
      }
}
