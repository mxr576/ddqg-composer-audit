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

namespace mxr576\ddqgComposerAudit\Presentation\Composer\Repository;

use Composer\IO\IOInterface;
use Composer\Repository\AdvisoryProviderInterface;
use Composer\Repository\ArrayRepository;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\Exception\PackageVersionsCouldNotBeFetched;
use mxr576\ddqgComposerAudit\Domain\SecurityAdvisory\SecurityAdvisoryFinder;
use mxr576\ddqgComposerAudit\Presentation\Composer\Plugin;

/**
 * @internal
 */
final class ComposerAuditRepository extends ArrayRepository implements AdvisoryProviderInterface
{
    public function __construct(private SecurityAdvisoryFinder $securityAdvisoryFinder, private readonly IOInterface $io)
    {
        parent::__construct([]);
    }

    public function hasSecurityAdvisories(): bool
    {
        return true;
    }

    public function getSecurityAdvisories(array $packageConstraintMap, bool $allowPartialAdvisories = false): array
    {
        try {
            $advisories = $this->securityAdvisoryFinder->find($packageConstraintMap);
        } catch (PackageVersionsCouldNotBeFetched $e) {
            $this->io->error(sprintf('%s: %s', Plugin::PACKAGE_NAME, $e->getMessage()));

            return ['namesFound' => [], 'advisories' => []];
        }

        return ['namesFound' => array_keys($advisories), 'advisories' => $advisories];
    }
}
