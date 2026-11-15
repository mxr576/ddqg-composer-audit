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

namespace mxr576\ddqgComposerAudit\Domain\SecurityAdvisory;

use Composer\Advisory\SecurityAdvisory;
use Composer\Semver\Constraint\ConstraintInterface;
use mxr576\ddqgComposerAudit\Domain\DdqgCategory;

/**
 * @internal
 */
final class SecurityAdvisoryBuilder
{
    private readonly string $drupalProjectId;

    public function __construct(
        private readonly DdqgCategory $category,
        private readonly string $packageName,
        private readonly string $installedVersion,
        private readonly ConstraintInterface $affectedVersions,
    ) {
        // This may not be 100% correct but good enough.
        [, $module_name] = explode('/', $packageName);
        $this->drupalProjectId = $module_name;
    }

    public function build(): SecurityAdvisory
    {
        $id_parts = ['DDQG'];
        $id_parts[] = $this->category->type();
        $id_parts[] = str_replace('/', '-', $this->packageName);
        // Composer >=2.6.0 supports ignoring security advisories. We would like to
        // push projects to depend on stable components, so let's make
        // ignoring unsupported/deprecated packages with that feature as
        // painful as it is possible with the currently available built-in
        // ignore features for unsupported/deprecated packages.
        // @see https://github.com/mxr576/ddqg-composer-audit/tree/db594420c127acc0375ff90dd7382c697f2e0375#silence-warning-about-an-unsupported-package-version
        if ($this->category->idIncludesVersion()) {
            $id_parts[] = $this->installedVersion;
        }
        $id = implode('-', $id_parts);

        return new SecurityAdvisory(
            $this->packageName,
            $id,
            $this->affectedVersions,
            $this->category->title($this->installedVersion) . ' (Reported by <href=https://packagist.org/packages/mxr576/ddqg>Drupal Dependency Quality Gate</>.)',
            [
                [
                    'name' => 'DDQG',
                    'remoteId' => $id,
                ],
            ],
            new \DateTimeImmutable(),
            $id,
            'https://www.drupal.org/project/' . $this->drupalProjectId,
        );
    }
}
