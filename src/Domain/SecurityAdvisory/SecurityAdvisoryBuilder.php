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

namespace mxr576\ddqgComposerAudit\Domain\SecurityAdvisory;

use Composer\Advisory\SecurityAdvisory;
use Composer\Semver\Constraint\ConstraintInterface;

/**
 * @internal
 */
final class SecurityAdvisoryBuilder
{
    private const TYPE_NON_D10_COMPATIBLE = 'D10-incompatible';

    private const TYPE_UNSUPPORTED = 'unsupported';

    private const TYPE_DEPRECATED = 'deprecated';

    private const TYPE_INSECURE = 'insecure';

    private readonly string $drupalProjectId;

    private string $title = 'Low quality dependency detected.';

    /**
     * @phpstan-var self::TYPE_*
     */
    private string $type;

    public function __construct(private readonly string $packageName, private readonly string $installedVersion, private readonly ConstraintInterface $affectedVersions)
    {
        // This may not be 100% correct but good enough.
        [, $module_name] = explode('/', $packageName);
        $this->drupalProjectId = $module_name;
    }

    public function becauseInsecure(): self
    {
        $this->title = sprintf('The installed "%s" version is insecure.', $this->installedVersion);
        $this->type = self::TYPE_INSECURE;

        return $this;
    }

    public function becauseUnsupported(): self
    {
        $this->title = sprintf('The installed "%s" version is unsupported.', $this->installedVersion);
        $this->type = self::TYPE_UNSUPPORTED;

        return $this;
    }

    public function becauseDeprecated(): self
    {
        $this->title = sprintf('The installed "%s" version is deprecated.', $this->installedVersion);
        $this->type = self::TYPE_DEPRECATED;

        return $this;
    }

    public function becauseNotCompatibleWithDrupal10(): self
    {
        $this->title = sprintf('The installed "%s" version is not compatible with Drupal 10.', $this->installedVersion);
        $this->type = self::TYPE_NON_D10_COMPATIBLE;

        return $this;
    }

    public function build(): SecurityAdvisory
    {
        $id_parts = ['DDQG'];
        $id_parts[] = $this->type;
        $id_parts[] = str_replace('/', '-', $this->packageName);
        // Composer >=2.6.0 supports ignoring security advisories. We would like to
        // push projects to depend on stable components, so let's make
        // ignoring unsupported/deprecated packages with that feature as
        // painful as it is possible with the currently available built-in
        // ignore features for unsupported/deprecated packages.
        // @see https://github.com/mxr576/ddqg-composer-audit/tree/db594420c127acc0375ff90dd7382c697f2e0375#silence-warning-about-an-unsupported-package-version
        if (in_array($this->type, [self::TYPE_UNSUPPORTED, self::TYPE_DEPRECATED], true)) {
            $id_parts[] = $this->installedVersion;
        }
        $id = implode('-', $id_parts);

        return new SecurityAdvisory(
            $this->packageName,
            $id,
            $this->affectedVersions,
            $this->title . ' (Reported by <href=https://packagist.org/packages/mxr576/ddqg>Drupal Dependency Quality Gate</>.)',
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
