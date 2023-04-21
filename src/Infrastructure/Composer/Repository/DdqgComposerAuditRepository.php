<?php

declare(strict_types=1);

namespace mxr576\ddqgComposerAudit\Infrastructure\Composer\Repository;

use Composer\IO\IOInterface;
use Composer\Repository\AdvisoryProviderInterface;
use Composer\Repository\ArrayRepository;
use Composer\Semver\VersionParser;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\InsecurePackageVersionsProvider;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\PackageVersionsCouldNotBeFetched;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\UnsupportedPackageVersionsProvider;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\Plugin;
use mxr576\ddqgComposerAudit\Infrastructure\Composer\Utility\SecurityAdvisoryBuilder;

/**
 * @internal
 */
final class DdqgComposerAuditRepository extends ArrayRepository implements AdvisoryProviderInterface
{
    /**
     * Constructs a new object.
     *
     * @param \mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider[] $problematicPackageVersionsProviders
     */
    public function __construct(private readonly array $problematicPackageVersionsProviders, private readonly VersionParser $versionParser, private readonly IOInterface $io)
    {
        parent::__construct([]);
    }

    public function hasSecurityAdvisories(): bool
    {
        return true;
    }

    public function getSecurityAdvisories(array $packageConstraintMap, bool $allowPartialAdvisories = false): array
    {
        $names_found = [];
        $advisories = [];

        $filter_by_packages = array_keys($packageConstraintMap);
        foreach ($this->problematicPackageVersionsProviders as $provider) {
            try {
                foreach ($provider->findByPackages(...$filter_by_packages) as $package_name => $version_range) {
                    $package_version_constraint = $this->versionParser->parseConstraints($version_range);
                    if ($package_version_constraint->matches($packageConstraintMap[$package_name])) {
                        $names_found[] = $package_name;
                        // getVersion() only exists on \Composer\Semver\Constraint\Constraint::getVersion().
                        // \Composer\Semver\Constraint\ConstraintInterface::getPrettyString()
                        // returns a version constraint.
                        // Let's try to figure out the installed version from something
                        // that is part of the interface and returns a version
                        // string (even if uses Composer's internal 4 digit
                        // representation).
                        $installed_version = $this->versionParser->normalize($packageConstraintMap[$package_name]->getLowerBound()->getVersion());
                        $advisory = new SecurityAdvisoryBuilder($package_name, $installed_version, $package_version_constraint);
                        if ($provider instanceof UnsupportedPackageVersionsProvider) {
                            $advisory->becauseUnsupported();
                        } elseif ($provider instanceof InsecurePackageVersionsProvider) {
                            $advisory->becauseInsecure();
                        }

                        $advisories[$package_name] = [
                          $advisory->build(),
                        ];
                    }
                }
            } catch (PackageVersionsCouldNotBeFetched $e) {
                // @todo Inject IO and expose information to the parent process.
            }
        }

        return ['namesFound' => $names_found, 'advisories' => $advisories];
    }
}
