<?php

declare(strict_types=1);

namespace mxr576\ddqgComposerAudit\Infrastructure\Ddqg;

use Composer\Downloader\TransportException;
use Composer\Util\HttpDownloader;
use JsonMachine\Items;
use loophp\collection\Collection;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\PackageVersionsCouldNotBeFetched;
use mxr576\ddqgComposerAudit\Domain\PackageVersionsProvider\ProblematicPackageVersionsProvider;

/**
 * @internal
 */
abstract class PackageVersionsFromRemoteComposerJsonRepository implements ProblematicPackageVersionsProvider
{
    public function __construct(private readonly string $url, private readonly HttpDownloader $httpDownloader)
    {
    }

    /**
     * {@inheritDoc}
     */
    final public function findByPackages(string ...$package_names): array
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

        $json = Collection::fromIterable(Items::fromString($composer_json));

        foreach ($json as $key => $value) {
            if ('conflict' === $key) {
                return Collection::fromIterable((array) $value)
                  ->filter(static fn ($value, $key): bool => in_array($key, $package_names, true))
                  ->all(false);
            }
        }

        return [];
    }
}
