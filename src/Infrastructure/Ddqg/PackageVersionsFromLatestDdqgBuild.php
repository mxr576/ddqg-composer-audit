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

namespace mxr576\ddqgComposerAudit\Infrastructure\Ddqg;

use Composer\Downloader\TransportException;
use Composer\Util\HttpDownloader;
use JsonMachine\StringChunks;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\Exception\PackageVersionsCouldNotBeFetched;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;

/**
 * Serves the problematic package versions of a DDQG branch from its latest
 * build.
 *
 * @internal
 */
final class PackageVersionsFromLatestDdqgBuild implements ProblematicPackageVersionsProvider
{
    private readonly string $url;

    public function __construct(DdqgBranch $branch, private readonly HttpDownloader $httpDownloader)
    {
        $this->url = match ($branch) {
            DdqgBranch::Insecure => 'https://raw.githubusercontent.com/mxr576/ddqg/no-insecure-versions/composer.json',
            DdqgBranch::Unsupported => 'https://raw.githubusercontent.com/mxr576/ddqg/no-unsupported-versions/composer.json',
            DdqgBranch::Deprecated => 'https://raw.githubusercontent.com/mxr576/ddqg/no-deprecated-versions/composer.json',
        };
    }

    public function findByPackages(array $packageConstraintMap): array
    {
        // Streaming the response would be good, but using native Composer tools
        // is better instead of depending on new 3rd party packages that may
        // conflict with project dependencies.
        try {
            $composer_json = $this->httpDownloader->get($this->url)->getBody();
        } catch (TransportException $e) {
            throw new PackageVersionsCouldNotBeFetched(sprintf('Failed to fetch package information from "%s". Reason: %s', $this->url, $e->getMessage()), $e->getCode(), $e);
        }

        assert(null !== $composer_json);

        return PackageVersionConstraintsFromDdqgComposerJson::extract(new StringChunks($composer_json), array_keys($packageConstraintMap));
    }
}
