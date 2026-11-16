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

namespace mxr576\ddqgComposerAudit\Tests\Application\PackageFinder;

use Composer\Package\CompletePackage;
use Composer\Package\Link;
use Composer\Package\RootPackage;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\LockArrayRepository;
use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Application\PackageFinder\Event\FindingWasIgnored;
use mxr576\ddqgComposerAudit\Application\PackageFinder\FindPackagesByDdqgCategory;
use mxr576\ddqgComposerAudit\Domain\DdqgCategory;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\CompositePackageIgnoreRuleProvider;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\IgnorePackageByVersionConstraintMatch;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\IgnoreVariationcacheMovedIntoCore;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\NoPackageIgnoreRules;
use mxr576\ddqgComposerAudit\Domain\PackageIgnore\PackageIgnoreRuleProvider;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\Exception\PackageVersionsCouldNotBeFetched;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\DeprecatedPackageIgnoreRulesFromConfiguration;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\NonDrupal10CompatiblePackageVersionsProviderFromPackageMetadata;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\PackageMetadataLookupFromComposerRepositories;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\UnsupportedPackageIgnoreRulesFromConfiguration;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\DdqgBranch;
use mxr576\ddqgComposerAudit\Infrastructure\Ddqg\PackageVersionsFromSnapshotForTesting;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FindPackagesByDdqgCategoryTest extends TestCase
{
    private const string D10_PACKAGE = 'drupal/foo';

    /**
     * Rules shared by every category live in the one finder implementation,
     * so they are tested once, through any category.
     */
    private const DdqgCategory SHARED_RULE_CATEGORY = DdqgCategory::Insecure;

    /**
     * Shared rules that show in the advisory ID need a category whose ID
     * includes the version.
     */
    private const DdqgCategory VERSIONED_ID_SHARED_RULE_CATEGORY = DdqgCategory::Unsupported;

    /**
     * @return iterable<string,array{DdqgCategory, string, string, string, string}>
     *   Category, snapshot package, offending version, expected advisory ID
     *   and expected title.
     */
    public static function categories(): iterable
    {
        yield 'insecure' => [DdqgCategory::Insecure, 'drupal/addtoany', '1.5.0', 'DDQG-insecure-drupal-addtoany', 'The installed "1.5.0.0" version is insecure.'];
        yield 'unsupported' => [DdqgCategory::Unsupported, 'drupal/a11y_form_helpers', '1.0.0', 'DDQG-unsupported-drupal-a11y_form_helpers-1.0.0.0', 'The installed "1.0.0.0" version is unsupported.'];
        yield 'deprecated' => [DdqgCategory::Deprecated, 'drupal/ablock', '1.0.0', 'DDQG-deprecated-drupal-ablock-1.0.0.0', 'The installed "1.0.0.0" version is deprecated.'];
        yield 'D10-incompatible' => [DdqgCategory::D10Incompatible, self::D10_PACKAGE, '1.0.0', 'DDQG-D10-incompatible-drupal-foo', 'The installed "1.0.0.0" version is not compatible with Drupal 10.'];
    }

    /**
     * @return iterable<string,array{DdqgCategory}>
     */
    public static function categoriesWithIgnoreSetting(): iterable
    {
        yield 'unsupported' => [DdqgCategory::Unsupported];
        yield 'deprecated' => [DdqgCategory::Deprecated];
    }

    /**
     * @param non-empty-string $expectedTitle
     *
     * @throws PackageVersionsCouldNotBeFetched
     */
    #[DataProvider('categories')]
    public function testAdvisoryIdAndTitle(DdqgCategory $category, string $package, string $version, string $expectedId, string $expectedTitle): void
    {
        $result = $this->snapshotFinder($category)([$package => $this->exact($version)]);

        $message = $category->value . ' finding';
        self::assertSame([$package], array_keys($result), $message . ' is reported for the snapshot package.');
        self::assertSame($package, $result[$package]->packageName, $message . ' names the package.');
        self::assertSame($expectedId, $result[$package]->id(), $message . ' has the expected ID.');
        self::assertSame($expectedTitle, $result[$package]->title(), $message . ' has the expected plain-text title.');
    }

    /**
     * @throws PackageVersionsCouldNotBeFetched
     */
    #[DataProvider('categoriesWithIgnoreSetting')]
    public function testPluginIgnoreSettingRemovesAdvisory(DdqgCategory $category): void
    {
        $setting = DdqgCategory::Unsupported === $category ? 'ignore-unsupported-versions' : 'ignore-deprecated-versions';
        $root = new RootPackage('test/root', '1.0.0.0', '1.0.0');
        $root->setExtra(['ddqg-composer-audit' => [$setting => ['drupal/foo' => '1.0.0']]]);
        $finder = $this->build($category, $this->rangesSource(['drupal/foo' => '*']), $this->ignoreRulesFromSetting($category, $root));

        $ignored = $finder(['drupal/foo' => $this->exact('1.0.0')]);
        $other = $finder(['drupal/foo' => $this->exact('2.0.0')]);

        self::assertSame([], $ignored, $category->value . ': the version named by the ignore setting is not reported.');
        self::assertSame(['drupal/foo'], array_keys($other), $category->value . ': another version is still reported.');
    }

    /**
     * @throws PackageVersionsCouldNotBeFetched
     */
    public function testSecondMatchingIgnoreRuleDoesNotCrashAndIgnoresOnce(): void
    {
        $root = new RootPackage('test/root', '1.0.0.0', '1.0.0');
        $root->setExtra(['ddqg-composer-audit' => ['ignore-deprecated-versions' => ['drupal/variationcache' => '1.0.0']]]);
        $configuredRule = new DeprecatedPackageIgnoreRulesFromConfiguration($root, $this->parser());
        $builtInRule = new IgnoreVariationcacheMovedIntoCore(new PackageMetadataLookupFromComposerRepositories(new InstalledArrayRepository([$this->package('drupal/core', '10.1.0')]), static fn (): ?LockArrayRepository => null));
        $dispatcher = new RecordingEventDispatcher();
        $finder = new FindPackagesByDdqgCategory(
            DdqgCategory::Deprecated,
            $this->rangesSource(['drupal/variationcache' => '*', 'drupal/foo' => '*']),
            $this->parser(),
            $dispatcher,
            new CompositePackageIgnoreRuleProvider($configuredRule, $builtInRule),
        );

        $result = $finder(['drupal/variationcache' => $this->exact('1.0.0'), 'drupal/foo' => $this->exact('1.0.0')]);

        self::assertSame(['drupal/foo'], array_keys($result), 'deprecated: only the non-ignored package is reported.');
        self::assertCount(1, $dispatcher->events, 'deprecated: one ignored finding yields one event.');
        $event = $dispatcher->events[0];
        self::assertInstanceOf(FindingWasIgnored::class, $event, 'deprecated: the event is an FindingWasIgnored.');
        self::assertSame('drupal/variationcache', $event->finding->packageName, 'deprecated: the event names the ignored package.');
        self::assertInstanceOf(IgnorePackageByVersionConstraintMatch::class, $event->ignoreRule, 'deprecated: the first matching rule, the configured one, is carried.');
    }

    /**
     * @throws PackageVersionsCouldNotBeFetched
     */
    public function testExactVersionInsideAndOutsideRange(): void
    {
        $finder = $this->build(self::SHARED_RULE_CATEGORY, $this->rangesSource(['drupal/foo' => '>=1.0.0,<2.0.0']));

        self::assertSame(['drupal/foo'], array_keys($finder(['drupal/foo' => $this->exact('1.5.0')])), 'A version inside the range is reported.');
        self::assertSame([], $finder(['drupal/foo' => $this->exact('2.5.0')]), 'A version outside the range is not reported.');
    }

    /**
     * @throws PackageVersionsCouldNotBeFetched
     */
    public function testOrOfCandidatesMatchesOnlyOffendingCandidates(): void
    {
        $finder = $this->build(self::SHARED_RULE_CATEGORY, $this->rangesSource(['drupal/foo' => '>=1.0.0,<2.0.0']));

        $result = $finder(['drupal/foo' => $this->parser()->parseConstraints('=1.5.0 || =2.5.0')]);

        self::assertSame(['drupal/foo'], array_keys($result), 'One finding for the offending candidate.');
        $affected = $result['drupal/foo']->affectedVersions;
        self::assertTrue($affected->matches($this->exact('1.5.0')), 'Offending candidate 1.5.0 is affected.');
        self::assertFalse($affected->matches($this->exact('2.5.0')), 'Non-offending candidate 2.5.0 is not affected.');
    }

    /**
     * @throws PackageVersionsCouldNotBeFetched
     */
    public function testOrOfCandidatesNamesLowestOffendingCandidate(): void
    {
        $category = self::VERSIONED_ID_SHARED_RULE_CATEGORY;
        $finder = $this->build($category, $this->rangesSource(['drupal/foo' => '>=2.0.0,<3.0.0']));

        $result = $finder(['drupal/foo' => $this->parser()->parseConstraints('=1.5.0 || =2.7.0 || =2.5.0 || =3.5.0')]);

        $message = $category->value . ' finding for candidates 1.5.0, 2.7.0, 2.5.0 and 3.5.0 in range >=2.0.0,<3.0.0';
        self::assertSame(['drupal/foo'], array_keys($result), $message . ' is a single finding.');
        self::assertSame('DDQG-' . $category->type() . '-drupal-foo-2.5.0.0', $result['drupal/foo']->id(), $message . ' has an ID naming the lowest offending candidate 2.5.0.');
        self::assertStringStartsWith('The installed "2.5.0.0" version ', $result['drupal/foo']->title(), $message . ' has a title naming the lowest offending candidate 2.5.0.');
    }

    /**
     * @throws PackageVersionsCouldNotBeFetched
     */
    public function testSourceFailurePropagates(): void
    {
        $this->expectException(PackageVersionsCouldNotBeFetched::class);

        $this->build(self::SHARED_RULE_CATEGORY, $this->rangesSource([], true))(['drupal/foo' => $this->exact('1.0.0')]);
    }

    /**
     * VariationCache moved into core in 10.2.0, so it is only reported as
     * deprecated for projects that have such a core.
     *
     * @throws PackageVersionsCouldNotBeFetched
     */
    public function testVariationcacheIsFlaggedWhileCore102OrNewerIsInstalled(): void
    {
        $result = $this->variationcacheFinder(new InstalledArrayRepository([$this->package('drupal/core', '10.2.0')]), null)(['drupal/variationcache' => $this->exact('1.0.0')]);

        self::assertSame(['drupal/variationcache'], array_keys($result), 'deprecated: core 10.2.0 is installed.');
    }

    /**
     * @throws PackageVersionsCouldNotBeFetched
     */
    public function testVariationcacheIsFlaggedWhileCore102OrNewerIsLocked(): void
    {
        $result = $this->variationcacheFinder(new InstalledArrayRepository(), new LockArrayRepository([$this->package('drupal/core', '10.3.1')]))(['drupal/variationcache' => $this->exact('1.0.0')]);

        self::assertSame(['drupal/variationcache'], array_keys($result), 'deprecated: core 10.3.1 is locked.');
    }

    /**
     * @throws PackageVersionsCouldNotBeFetched
     */
    public function testVariationcacheIsNotFlaggedWithOlderOrNoCore(): void
    {
        $constraint = ['drupal/variationcache' => $this->exact('1.0.0')];

        $older = $this->variationcacheFinder(new InstalledArrayRepository([$this->package('drupal/core', '10.1.0')]), null)($constraint);
        $none = $this->variationcacheFinder(new InstalledArrayRepository(), null)($constraint);

        self::assertSame([], $older, 'deprecated: core 10.1.0 keeps the package ignored.');
        self::assertSame([], $none, 'deprecated: without core metadata and without a lock file the package stays ignored.');
    }

    /**
     * @throws PackageVersionsCouldNotBeFetched
     */
    public function testD10VersionCompatibleWithDrupal10IsNotFlagged(): void
    {
        $installed = new InstalledArrayRepository([$this->module('1.0.0', '^9.4 || ^10')]);

        self::assertSame([], $this->d10Finder($installed, null)([self::D10_PACKAGE => $this->exact('1.0.0')]), 'D10-incompatible: a compatible version is not flagged.');
    }

    /**
     * @throws PackageVersionsCouldNotBeFetched
     */
    public function testD10VersionWithoutMetadataYieldsNothing(): void
    {
        $installed = new InstalledArrayRepository([$this->module('1.0.0', '^9.4')]);

        self::assertSame([], $this->d10Finder($installed, null)([self::D10_PACKAGE => $this->exact('2.0.0')]), 'D10-incompatible: no metadata, no finding.');
    }

    /**
     * @throws PackageVersionsCouldNotBeFetched
     */
    public function testD10OrOfCandidatesJudgesEachVersionOnItsOwn(): void
    {
        // The compatible version is installed, the incompatible one is locked.
        $installed = new InstalledArrayRepository([$this->module('1.0.0', '^10')]);
        $locked = new LockArrayRepository([$this->module('2.0.0', '^9.4')]);

        $result = $this->d10Finder($installed, $locked)([self::D10_PACKAGE => $this->parser()->parseConstraints('=1.0.0 || =2.0.0')]);

        self::assertSame([self::D10_PACKAGE], array_keys($result), 'D10-incompatible: the incompatible candidate 2.0.0 yields a finding.');
        $affected = $result[self::D10_PACKAGE]->affectedVersions;
        self::assertTrue($affected->matches($this->exact('2.0.0')), 'D10-incompatible: incompatible candidate 2.0.0 is affected.');
        self::assertFalse($affected->matches($this->exact('1.0.0')), 'D10-incompatible: compatible candidate 1.0.0 is not affected.');
    }

    /**
     * @throws PackageVersionsCouldNotBeFetched
     */
    public function testD10MetadataIsFoundInLockFileWhenNothingIsInstalled(): void
    {
        $locked = new LockArrayRepository([$this->module('1.0.0', '^9.4')]);

        $result = $this->d10Finder(new InstalledArrayRepository(), $locked)([self::D10_PACKAGE => $this->exact('1.0.0')]);

        self::assertSame([self::D10_PACKAGE], array_keys($result), 'D10-incompatible: metadata is found in the lock file.');
    }

    private function snapshotFinder(DdqgCategory $category): FindPackagesByDdqgCategory
    {
        if (DdqgCategory::D10Incompatible === $category) {
            return $this->d10Finder(new InstalledArrayRepository([$this->module('1.0.0', '^9.4')]), null);
        }

        $branch = match ($category) {
            DdqgCategory::Insecure => DdqgBranch::Insecure,
            DdqgCategory::Unsupported => DdqgBranch::Unsupported,
            DdqgCategory::Deprecated => DdqgBranch::Deprecated,
        };

        return $this->build($category, new PackageVersionsFromSnapshotForTesting($branch));
    }

    private function d10Finder(InstalledArrayRepository $installed, ?LockArrayRepository $locked): FindPackagesByDdqgCategory
    {
        return $this->build(
            DdqgCategory::D10Incompatible,
            new NonDrupal10CompatiblePackageVersionsProviderFromPackageMetadata(
                new PackageMetadataLookupFromComposerRepositories($installed, static fn (): ?LockArrayRepository => $locked),
                $this->parser(),
            ),
        );
    }

    private function variationcacheFinder(InstalledArrayRepository $installed, ?LockArrayRepository $locked): FindPackagesByDdqgCategory
    {
        return $this->build(
            DdqgCategory::Deprecated,
            $this->rangesSource(['drupal/variationcache' => '*']),
            new IgnoreVariationcacheMovedIntoCore(new PackageMetadataLookupFromComposerRepositories($installed, static fn (): ?LockArrayRepository => $locked)),
        );
    }

    private function ignoreRulesFromSetting(DdqgCategory $category, RootPackage $root): PackageIgnoreRuleProvider
    {
        $parser = $this->parser();

        return DdqgCategory::Unsupported === $category
            ? new UnsupportedPackageIgnoreRulesFromConfiguration($root, $parser)
            : new DeprecatedPackageIgnoreRulesFromConfiguration($root, $parser);
    }

    private function build(DdqgCategory $category, ProblematicPackageVersionsProvider $source, ?PackageIgnoreRuleProvider $ignoreRules = null): FindPackagesByDdqgCategory
    {
        return new FindPackagesByDdqgCategory($category, $source, $this->parser(), new NullEventDispatcher(), $ignoreRules ?? new NoPackageIgnoreRules());
    }

    /**
     * @param array<string,string> $ranges
     */
    private function rangesSource(array $ranges, bool $fails = false): ProblematicPackageVersionsProvider
    {
        return new class($ranges, $fails) implements ProblematicPackageVersionsProvider {
            use RangesProviderStub;
        };
    }

    private function exact(string $version): ConstraintInterface
    {
        return $this->parser()->parseConstraints('=' . $version);
    }

    private function package(string $name, string $version): CompletePackage
    {
        return new CompletePackage($name, $this->parser()->normalize($version), $version);
    }

    private function module(string $version, string $coreConstraint): CompletePackage
    {
        $package = $this->package(self::D10_PACKAGE, $version);
        $package->setType('drupal-module');
        $package->setRequires(['drupal/core' => new Link(self::D10_PACKAGE, 'drupal/core', $this->parser()->parseConstraints($coreConstraint), Link::TYPE_REQUIRE, $coreConstraint)]);

        return $package;
    }

    private function parser(): VersionParser
    {
        return new VersionParser();
    }
}
