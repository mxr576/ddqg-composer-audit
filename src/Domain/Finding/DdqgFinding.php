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

namespace mxr576\ddqgComposerAudit\Domain\Finding;

use Composer\Semver\Constraint\ConstraintInterface;
use mxr576\ddqgComposerAudit\Domain\DdqgCategory;

/**
 * What a DDQG category reports about one package of the audited set.
 *
 * Carries no delivery details (markup, links, report time): the consumer
 * adds those when it turns the finding into its own output.
 *
 * @internal
 */
final readonly class DdqgFinding
{
    /**
     * @param string $offendingVersion
     *   The offending candidate, the version the ID and the title name.
     * @param ConstraintInterface $affectedVersions
     *   The problematic range of the category's source.
     */
    public function __construct(
        public DdqgCategory $category,
        public string $packageName,
        public string $offendingVersion,
        public ConstraintInterface $affectedVersions,
    ) {
    }

    /**
     * The advisory ID, which Composer's `audit.ignore` setting matches.
     *
     * Keep the format stable: changing it breaks users' ignore settings.
     */
    public function id(): string
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
            $id_parts[] = $this->offendingVersion;
        }

        return implode('-', $id_parts);
    }

    /**
     * Plain-text title, without markup.
     */
    public function title(): string
    {
        return $this->category->title($this->offendingVersion);
    }
}
