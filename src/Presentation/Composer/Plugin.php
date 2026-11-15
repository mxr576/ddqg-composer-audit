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
use mxr576\ddqgComposerAudit\Presentation\Composer\Event\AdvisoryWasIgnoredAdapter;
use mxr576\ddqgComposerAudit\Presentation\Composer\Repository\ComposerAuditRepository;
use mxr576\ddqgComposerAudit\Presentation\Composer\Wiring\PackageFinderFactory;
use mxr576\ddqgComposerAudit\Presentation\Composer\Wiring\PackageMetadataLookupFactory;

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
        $finders = (new PackageFinderFactory(
            $composer->getPackage(),
            $composer->getLoop()->getHttpDownloader(),
            $composer->getEventDispatcher(),
            new VersionParser(),
            (new PackageMetadataLookupFactory())->create($composer),
        ))->createForEnabledCategories();

        // One repository per DDQG category, not one for all: each repository
        // catches its own fetch failure, so an unreachable DDQG branch empties
        // only its category and the others still report. The factory returns
        // the finders in the order they have always been prepended, which
        // keeps the order of advisories in the audit output.
        foreach ($finders as $finder) {
            $composer->getRepositoryManager()->prependRepository(new ComposerAuditRepository($finder, $io));
        }
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public function warnAboutIgnoredAdvisory(AdvisoryWasIgnoredAdapter $event): void
    {
        $this->displayConsoleMessages($this->io, sprintf('<comment>An advisory about the %s "%s" package was ignored by configuration.</comment>', $event->wrapped->category->type(), $event->wrapped->packageName));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            AdvisoryWasIgnoredAdapter::class => 'warnAboutIgnoredAdvisory',
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
