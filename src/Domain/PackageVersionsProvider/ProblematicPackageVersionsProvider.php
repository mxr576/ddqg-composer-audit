<?php

declare(strict_types=1);

namespace mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider;

interface ProblematicPackageVersionsProvider
{
    /**
     * @phpstan-return array<string,string>
     *
     * @throws \mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\PackageVersionsCouldNotBeFetched
     */
    public function findByPackages(string ...$package_names): array;
}
