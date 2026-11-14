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

namespace mxr576\ddqgComposerAudit\Presentation\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionParser;
use Composer\Plugin\PluginInterface;
use mxr576\ddqgComposerAudit\Presentation\Composer\Repository\ComposerAuditRepository;
use mxr576\ddqgComposerAudit\Supportive\Adapter\Composer\DeprecatedPackageWasIgnoredAdapter;
use mxr576\ddqgComposerAudit\Supportive\Adapter\Composer\UnsupportedPackageWasIgnoredAdapter;
use mxr576\ddqgComposerAudit\Supportive\Factory\FindDeprecatedPackagesFactory;
use mxr576\ddqgComposerAudit\Supportive\Factory\FindInsecurePackagesFactoryFromComposerRuntimeDependencies;
use mxr576\ddqgComposerAudit\Supportive\Factory\FindNonDrupal10CompatiblePackagesFactory;
use mxr576\ddqgComposerAudit\Supportive\Factory\FindUnsupportedPackagesFactoryFromComposerRuntimeDependencies;
use mxr576\ddqgComposerAudit\Supportive\Factory\PackageMetadataLookupFactory;

/**
 * @internal
 */
final class Plugin implements PluginInterface, EventSubscriberInterface
{
    public const PACKAGE_NAME = 'mxr576/ddqg-composer-audit';

    private IOInterface $io;

    public function __construct()
    {
        $this->io = new NullIO();
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;
        $version_parser = new VersionParser();
        $package_metadata_lookup = (new PackageMetadataLookupFactory())->create($composer);

        // Composer currently only displays advisories from one repository for
        // a package. If multiple ones provides advisories only the first one
        // is visible.
        // @see https://github.com/composer/composer/issues/11435.
        $composer->getRepositoryManager()->prependRepository(
            new ComposerAuditRepository(
                (new FindUnsupportedPackagesFactoryFromComposerRuntimeDependencies(
                    $composer->getPackage(),
                    $composer->getLoop()->getHttpDownloader(),
                    $composer->getEventDispatcher(),
                    $version_parser,
                ))->create(), $io
            )
        );
        $composer->getRepositoryManager()->prependRepository(
            new ComposerAuditRepository(
                (new FindDeprecatedPackagesFactory(
                    $composer->getPackage(),
                    $composer->getLoop()->getHttpDownloader(),
                    $composer->getEventDispatcher(),
                    $version_parser,
                ))->create($package_metadata_lookup),
                $io
            )
        );
        $composer->getRepositoryManager()->prependRepository(
            new ComposerAuditRepository(
                (new FindInsecurePackagesFactoryFromComposerRuntimeDependencies($composer->getLoop()->getHttpDownloader(), $version_parser))->create(),
                $io
            )
        );
        $composer->getRepositoryManager()->prependRepository(
            new ComposerAuditRepository(
                (new FindNonDrupal10CompatiblePackagesFactory($composer->getPackage(), $version_parser))->create($package_metadata_lookup),
                $io
            )
        );
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public function warnAboutIgnoredUnsupportedPackage(UnsupportedPackageWasIgnoredAdapter $event): void
    {
        $this->displayConsoleMessages($this->io, sprintf('<comment>An advisory about the unsupported "%s" package was ignored by configuration.</comment>', $event->packageName));
    }

    public function warnAboutIgnoredDeprecatedPackage(DeprecatedPackageWasIgnoredAdapter $event): void
    {
        $this->displayConsoleMessages($this->io, sprintf('<comment>An advisory about the deprecated "%s" package was ignored by configuration.</comment>', $event->packageName));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UnsupportedPackageWasIgnoredAdapter::class => 'warnAboutIgnoredUnsupportedPackage',
            DeprecatedPackageWasIgnoredAdapter::class => 'warnAboutIgnoredDeprecatedPackage',
        ];
    }

    private function displayConsoleMessages(IOInterface $io, string ...$messages): void
    {
        // By pushing this to STDERR we guarantee that the output on
        // STDOUT (e.g, in JSON) do not get malformed when it is captured.
        foreach ($messages as $message) {
            // writeError() always writes to STDERR or nowhere.
            // https://github.com/composer/composer/blob/2.6.2/src/Composer/IO/ConsoleIO.php#L172-L177
            $io->writeError(sprintf('[<href=https://packagist.org/packages/mxr576/ddqg-composer-audit>%s</>] ', self::PACKAGE_NAME) . $message);
        }
    }
}
