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

namespace mxr576\ddqgComposerAudit\Infrastructure\Composer\Utility;

use Composer\Advisory\SecurityAdvisory;
use Composer\Semver\Constraint\ConstraintInterface;

/**
 * @internal
 */
final class SecurityAdvisoryBuilder
{
    private readonly string $drupalProjectId;

    private string $title = 'Low quality dependency detected.';

    private string|null $type;

    public function __construct(private readonly string $packageName, private readonly string $installedVersion, private readonly ConstraintInterface $affectedVersions)
    {
        // This may not be 100% accurate but good enough.
        [, $module_name] = explode('/', $packageName);
        $this->drupalProjectId = $module_name;
    }

    public function becauseInsecure(): self
    {
        $this->title = sprintf('The installed "%s" version is insecure.', $this->installedVersion);
        $this->type = 'insecure';

        return $this;
    }

    public function becauseUnsupported(): self
    {
        $this->title = sprintf('The installed "%s" version is unsupported.', $this->installedVersion);
        $this->type = 'unsupported';

        return $this;
    }

    public function build(): SecurityAdvisory
    {
        $id_parts = ['DDQG'];
        if (null !== $this->type) {
            $id_parts[] = $this->type;
        }
        $id_parts[] = str_replace('/', '-', $this->packageName);
        $id = implode('-', $id_parts);

        return new SecurityAdvisory(
            $this->packageName,
            $id,
            $this->affectedVersions,
            $this->title . ' (Reported by Drupal Dependency Quality Gate.)',
            [
              [
                'name' => 'ddqg',
                'remoteId' => $id,
              ],
            ],
            new \DateTimeImmutable(),
            $id,
            'https://www.drupal.org/project/' . $this->drupalProjectId,
        );
    }
}
