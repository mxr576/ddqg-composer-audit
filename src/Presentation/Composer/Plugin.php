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

namespace mxr576\ddqgComposerAudit\Presentation\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Package\Version\VersionParser;
use Composer\Plugin\PluginInterface;
use mxr576\ddqgComposerAudit\Presentation\Composer\Repository\ComposerAuditRepository;
use mxr576\ddqgComposerAudit\Supportive\Adapter\Composer\UnsupportedPackageWasIgnoredAdapter;
use mxr576\ddqgComposerAudit\Supportive\Factory\FindInsecurePackagesFactoryFromComposerRuntimeDependencies;
use mxr576\ddqgComposerAudit\Supportive\Factory\FindNonDrupal10CompatiblePackagesFactoryFromComposerRuntimeDependencies;
use mxr576\ddqgComposerAudit\Supportive\Factory\FindUnsupportedPackagesFactoryFromComposerRuntimeDependencies;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

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

    /**
     * {@inheritDoc}
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->io = $io;
        // This is a runtime check instead of a dependency constraint to allow
        // installation of this package on projects where composer/composer also
        // installed as a project dependency (but a global version used instead).
        // @see https://github.com/mxr576/ddqg-composer-audit/issues/1
        if (version_compare($composer::VERSION, '2.4.0', '<')) {
            $io->warning(sprintf('%s is disabled because audit command is only available since Composer 2.4.0. Your version is: %s.', self::PACKAGE_NAME, $composer::VERSION));

            return;
        }

        $with_dev_dependencies = false;
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
            }
        }

        $version_parser = new VersionParser();
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
                (new FindInsecurePackagesFactoryFromComposerRuntimeDependencies($composer->getLoop()->getHttpDownloader(), $version_parser))->create(),
                $io
            )
        );
        $composer->getRepositoryManager()->prependRepository(
            new ComposerAuditRepository(
                (new FindNonDrupal10CompatiblePackagesFactoryFromComposerRuntimeDependencies($composer->getPackage(), $composer->getLocker()->getLockedRepository($with_dev_dependencies), $version_parser))->create(),
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

    public function warnAboutIgnoredPackage(UnsupportedPackageWasIgnoredAdapter $event): void
    {
        if ($this->io instanceof ConsoleIO) {
            $output = new ConsoleOutput();
            // Even if the plugin allows ignoring, it should not be completely
            // silent...
            // By pushing this to STDERR we guarantee that the output on
            // STDOUT (e.g, in JSON) do not get malformed when it is captured.
            $output->getErrorOutput()->writeln(
                sprintf('<comment>An advisory about the unsupported "%s" package was ignored by configuration.</comment>', $event->packageName)
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
          UnsupportedPackageWasIgnoredAdapter::class => 'warnAboutIgnoredPackage',
        ];
    }
}
