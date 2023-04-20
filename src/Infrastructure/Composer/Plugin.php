<?php

declare(strict_types=1);

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
    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $composer->getRepositoryManager()->prependRepository(
            new DdqgComposerAuditRepository(
                [
                  new UnsupportedPackageVersionsFromLatestDdqgBuild($composer->getLoop()->getHttpDownloader()),
                  new InsecurePackageVersionsFromLatestDdqgBuild($composer->getLoop()->getHttpDownloader()),
                ],
                new VersionParser()
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
