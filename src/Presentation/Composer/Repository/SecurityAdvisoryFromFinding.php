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

namespace mxr576\ddqgComposerAudit\Presentation\Composer\Repository;

use Composer\Advisory\SecurityAdvisory;
use mxr576\ddqgComposerAudit\Domain\Finding\DdqgFinding;

/**
 * Turns a DDQG finding into the advisory `composer audit` reports.
 *
 * Owns every delivery detail of the advisory: the attribution, the link and
 * the report time.
 *
 * @internal
 */
final readonly class SecurityAdvisoryFromFinding
{
    /**
     * Plain text: Composer renders the JSON format through its console
     * formatter too, so markup here becomes terminal escape sequences inside
     * the JSON whenever the output is decorated (e.g., `--ansi`).
     */
    private const string ATTRIBUTION = ' (Reported by Drupal Dependency Quality Gate: https://packagist.org/packages/mxr576/ddqg)';

    /**
     * @param \DateTimeImmutable $reportedAt
     *   When the plugin judged the audited set, the same for every advisory
     *   of a Composer run.
     */
    public function __construct(private \DateTimeImmutable $reportedAt)
    {
    }

    public function __invoke(DdqgFinding $finding): SecurityAdvisory
    {
        $id = $finding->id();

        return new SecurityAdvisory(
            $finding->packageName,
            $id,
            $finding->affectedVersions,
            $finding->title() . self::ATTRIBUTION,
            [
                [
                    'name' => 'DDQG',
                    'remoteId' => $id,
                ],
            ],
            $this->reportedAt,
            $id,
            $this->drupalProjectLink($finding->packageName),
        );
    }

    /**
     * The drupal.org project page of a package, if it has one.
     *
     * Contrib projects are published as drupal/{project}. Core and its
     * subtree splits (drupal/core-recommended, drupal/core-dev, ...) all
     * belong to the "drupal" project; contrib names never contain a hyphen,
     * so drupal/core_* stays contrib.
     */
    private function drupalProjectLink(string $packageName): ?string
    {
        if (!str_starts_with($packageName, 'drupal/')) {
            return null;
        }

        $project = substr($packageName, \strlen('drupal/'));
        if ('core' === $project || str_starts_with($project, 'core-')) {
            $project = 'drupal';
        }

        return 'https://www.drupal.org/project/' . $project;
    }
}
