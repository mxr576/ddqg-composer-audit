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

namespace mxr576\ddqgComposerAudit\Tests\Presentation\Composer\Repository;

use Composer\Advisory\SecurityAdvisory;
use Composer\IO\NullIO;
use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindPackagesByDdqgCategory;
use mxr576\ddqgComposerAudit\Domain\DdqgCategory;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\NoPackageIgnoreRules;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;
use mxr576\ddqgComposerAudit\Presentation\Composer\Repository\ComposerAuditRepository;
use mxr576\ddqgComposerAudit\Presentation\Composer\Repository\SecurityAdvisoryFromFinding;
use mxr576\ddqgComposerAudit\Tests\Application\PackageFinder\NullEventDispatcher;
use mxr576\ddqgComposerAudit\Tests\Application\PackageFinder\RangesProviderStub;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ComposerAuditRepositoryTest extends TestCase
{
    /**
     * @return iterable<string,array{string, string|null}>
     *   Package and the expected advisory link.
     */
    public static function projectLinks(): iterable
    {
        yield 'contrib' => ['drupal/feeds', 'https://www.drupal.org/project/feeds'];
        yield 'contrib starting with core_' => ['drupal/core_context', 'https://www.drupal.org/project/core_context'];
        yield 'core' => ['drupal/core', 'https://www.drupal.org/project/drupal'];
        yield 'core subtree split' => ['drupal/core-recommended', 'https://www.drupal.org/project/drupal'];
        yield 'not a Drupal project' => ['vendor/package', null];
    }

    public function testAdvisoryCarriesTheFinding(): void
    {
        $advisory = $this->advisoryFor('drupal/foo');

        self::assertSame('drupal/foo', $advisory->packageName, 'The advisory names the package.');
        self::assertSame('DDQG-unsupported-drupal-foo-1.0.0.0', $advisory->advisoryId, 'The advisory ID is the finding ID.');
        self::assertSame($advisory->advisoryId, $advisory->cve, 'The CVE field repeats the ID, as Composer shows it in the table.');
        self::assertSame([['name' => 'DDQG', 'remoteId' => $advisory->advisoryId]], $advisory->sources, 'DDQG is the source.');
        self::assertSame('1.0.0', $advisory->affectedVersions->getPrettyString(), 'The affected versions are the problematic range.');
    }

    public function testTitleIsPlainTextWithAttribution(): void
    {
        $advisory = $this->advisoryFor('drupal/foo');

        self::assertSame('The installed "1.0.0.0" version is unsupported. (Reported by Drupal Dependency Quality Gate: https://packagist.org/packages/mxr576/ddqg)', $advisory->title, 'The title is the plain-text category title with the attribution, without console markup.');
    }

    public function testReportedAtIsTheInjectedTime(): void
    {
        $reportedAt = new \DateTimeImmutable('2026-01-02T03:04:05+00:00');

        self::assertSame($reportedAt, $this->advisoryFor('drupal/foo', $reportedAt)->reportedAt, 'The advisory reports the time the plugin passed in.');
    }

    #[DataProvider('projectLinks')]
    public function testLinkPointsToTheDrupalProject(string $package, ?string $expectedLink): void
    {
        self::assertSame($expectedLink, $this->advisoryFor($package)->link, sprintf('%s links to %s.', $package, $expectedLink ?? 'nothing'));
    }

    private function advisoryFor(string $package, ?\DateTimeImmutable $reportedAt = null): SecurityAdvisory
    {
        $parser = new VersionParser();
        $source = new class([$package => '1.0.0']) implements ProblematicPackageVersionsProvider {
            use RangesProviderStub;
        };
        $repository = new ComposerAuditRepository(
            new FindPackagesByDdqgCategory(DdqgCategory::Unsupported, $source, $parser, new NullEventDispatcher(), new NoPackageIgnoreRules()),
            new SecurityAdvisoryFromFinding($reportedAt ?? new \DateTimeImmutable()),
            new NullIO(),
        );

        $result = $repository->getSecurityAdvisories([$package => $parser->parseConstraints('=1.0.0')]);

        self::assertSame([$package], $result['namesFound'], $package . ' is found.');
        $advisories = $result['advisories'][$package] ?? [];
        self::assertCount(1, $advisories, $package . ' has one advisory.');
        self::assertInstanceOf(SecurityAdvisory::class, $advisories[0], $package . ' has a full security advisory.');

        return $advisories[0];
    }
}
