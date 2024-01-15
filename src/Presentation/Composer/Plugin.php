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

namespace mxr576\ddqgComposerAudit\Presentation\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionParser;
use Composer\Plugin\PluginInterface;
use Composer\Repository\InstalledRepository;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\InstalledPackagesReadOnlyRepository;
use mxr576\ddqgComposerAudit\Presentation\Composer\Repository\ComposerAuditRepository;
use mxr576\ddqgComposerAudit\Supportive\Adapter\Composer\DeprecatedPackageWasIgnoredAdapter;
use mxr576\ddqgComposerAudit\Supportive\Adapter\Composer\UnsupportedPackageWasIgnoredAdapter;
use mxr576\ddqgComposerAudit\Supportive\Factory\FindDeprecatedPackagesFactoryFromComposerRuntimeDependencies;
use mxr576\ddqgComposerAudit\Supportive\Factory\FindInsecurePackagesFactoryFromComposerRuntimeDependencies;
use mxr576\ddqgComposerAudit\Supportive\Factory\FindNonDrupal10CompatiblePackagesFactory;
use mxr576\ddqgComposerAudit\Supportive\Factory\FindUnsupportedPackagesFactoryFromComposerRuntimeDependencies;
use Symfony\Component\Console\Input\InputInterface;

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
        // This is a runtime check instead of a dependency constraint to allow
        // installation of this package on projects where composer/composer also
        // installed as a project dependency (but a global version used instead).
        // @see https://github.com/mxr576/ddqg-composer-audit/issues/1
        if (version_compare($composer::getVersion(), '2.4.0', '<')) {
            $this->displayConsoleMessages($io, sprintf('Plugin is disabled because audit command is only available since Composer 2.4.0. Your version is: %s.', $composer::getVersion()));

            return;
        }

        if (version_compare(Composer::getVersion(), '2.6.0', '<')) {
            $this->displayConsoleMessages(
                $io,
                sprintf('<comment>Composer version %s detected. Composer >=2.6.0 is <href=https://github.com/mxr576/ddqg-composer-audit#composer-version-support>recommended</>.</comment>', $composer::getVersion()),
            );
        }

        $with_dev_dependencies = false;
        $locked_dependencies = false;
        // This is not great, not terrible... the only benefit of this is
        // probably reducing the amount of objects build by the
        // LockerRepository.
        // @see \Composer\Package\Locker::getLockedRepository()
        if ($io instanceof ConsoleIO) {
            $ro_io = new \ReflectionObject($io);
            if ($ro_io->hasProperty('input')) {
                $ro_io->getProperty('input')->setAccessible(true);
                assert($ro_io->getProperty('input')->getValue($io) instanceof InputInterface);
                $with_dev_dependencies = str_contains((string) $ro_io->getProperty('input')->getValue($io), '--no-dev');
                $locked_dependencies = str_contains((string) $ro_io->getProperty('input')->getValue($io), '--locked');
            }
        }

        $version_parser = new VersionParser();

        $d10_factory = new FindNonDrupal10CompatiblePackagesFactory($composer->getPackage(), $version_parser);
        if ($locked_dependencies) {
            $d10_non_compatible_finder = $with_dev_dependencies ? $d10_factory->inLockedDependencies($composer->getLocker()) : $d10_factory->inLockedRequiredDependencies($composer->getLocker());
        } else {
            $d10_non_compatible_finder = $with_dev_dependencies ? $d10_factory->inInstalledDependencies(new InstalledRepository([$composer->getRepositoryManager()->getLocalRepository()])) : $d10_factory->inInstalledRequiredDependencies(new InstalledRepository([$composer->getRepositoryManager()->getLocalRepository()]));
        }

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
                (new FindDeprecatedPackagesFactoryFromComposerRuntimeDependencies(
                    $composer->getPackage(),
                    $composer->getLoop()->getHttpDownloader(),
                    $composer->getEventDispatcher(),
                    $version_parser,
                    // @todo Fix baselined layering rule issues.
                    $locked_dependencies ? InstalledPackagesReadOnlyRepository::fromLocker($composer->getLocker(), $with_dev_dependencies) : ($with_dev_dependencies ? InstalledPackagesReadOnlyRepository::fromInstalledPackages(new InstalledRepository([$composer->getRepositoryManager()->getLocalRepository()])) : InstalledPackagesReadOnlyRepository::fromInstalledRequiredPackages(new InstalledRepository([$composer->getRepositoryManager()->getLocalRepository()]), $composer->getPackage()))
                ))->create(), $io
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
                $d10_non_compatible_finder,
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
            $io->writeError(sprintf('[<href=https://packagist.org/packages/mxr576/ddqg-composer-audit>%s</a>] ', self::PACKAGE_NAME) . $message);
        }
    }
}
