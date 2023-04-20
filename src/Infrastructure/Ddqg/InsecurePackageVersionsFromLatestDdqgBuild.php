<?php

declare(strict_types=1);

namespace mxr576\ddqgComposerAudit\Infrastructure\Ddqg;

use Composer\Util\HttpDownloader;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\InsecurePackageVersionsProvider;

/**
 * @internal
 */
final class InsecurePackageVersionsFromLatestDdqgBuild extends PackageVersionsFromRemoteComposerJsonRepository implements InsecurePackageVersionsProvider
{
    private const LATEST_RELEASE_URL = 'https://raw.githubusercontent.com/mxr576/ddqg/no-no-insecure-versions/composer.json';

    public function __construct(HttpDownloader $httpDownloader)
    {
        parent::__construct(self::LATEST_RELEASE_URL, $httpDownloader);
    }
}
