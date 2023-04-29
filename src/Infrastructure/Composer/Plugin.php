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

namespace mxr576\ddqgComposerAudit\Infrastructure\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\Version\VersionParser;
use Composer\Plugin\PluginInterface;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\Repository\DdqgComposerAuditRepository;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\InsecurePackageVersionsFromLatestDdqgBuild;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\UnsupportedPackageVersionsFromLatestDdqgBuild;

/**
 * @internal
 */
final class Plugin implements PluginInterface
{
    public const PACKAGE_NAME = 'mxr576/ddqg-composer-audit';

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        // This is a runtime check instead of a dependency constraint to allow
        // installation of this package on projects where composer/composer also
        // installed as a project dependency (but a global version used instead).
        // @see https://github.com/mxr576/ddqg-composer-audit/issues/1
        if (version_compare($composer::VERSION, '2.4.0', '<')) {
            $io->warning(sprintf('%s is disabled because audit command is only available since Composer 2.4.0. Your version is: %s.', self::PACKAGE_NAME, $composer::VERSION));

            return;
        }
        $composer->getRepositoryManager()->prependRepository(
            new DdqgComposerAuditRepository(
                [
                  new UnsupportedPackageVersionsFromLatestDdqgBuild($composer->getLoop()->getHttpDownloader()),
                  new InsecurePackageVersionsFromLatestDdqgBuild($composer->getLoop()->getHttpDownloader()),
                ],
                new VersionParser(),
                $io
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }
}
