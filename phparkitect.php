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

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $sourceFiles = ClassSet::fromDir(__DIR__ . '/src');

    $rules = [];

    $rules[] = Rule::allClasses()
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Domain'))
      ->should(new NotHaveDependencyOutsideNamespace('mxr576\ddqgComposerAudit\Domain', [
        'RuntimeException',
        'DateTimeImmutable',
        // We have to handle some Composer stuff as part of our domain, we do not
        // want to redevelop them in the scope of a Composer plugin.
        'Composer\Advisory\SecurityAdvisory',
        'Composer\Semver\VersionParser',
        'Composer\Semver\Constraint\ConstraintInterface',
        ]))
      ->because('We want to protect the Domain');

    $rules[] = Rule::allClasses()
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Domain'))
      ->should(new IsFinal())
      ->because('We want to protect our domain');

    // We do not have an Application layer (yet) because there were no reasons
    // for introducing it - like getting an
    // `array<string, \Composer\Semver\Constraint\ConstraintInterface>`
    // as `$packageConstraintMap` from Composer then turning it to a DTO and back
    // to $packageConstraintMap in the Domain layer - therefore Presentation layer
    // can depend on Domain and Infrastructure.
    $rules[] = Rule::allClasses()
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Infrastructure'))
      ->should(new NotDependsOnTheseNamespaces('mxr576\ddqgComposerAudit\Presentation'))
      ->because('Infrastructure layer should not depend on Presentation, just the other way around');

    $config
      ->add($sourceFiles, ...$rules);
};
