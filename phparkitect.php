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

    // We may only enforce these rules below if we add a service container
    // implementation since some of these failures are caused by building
    // objects with dependencies by hand.

    //    $rules[] = Rule::allClasses()
    //      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Infrastructure'))
    //      ->should(new NotDependsOnTheseNamespaces('mxr576\ddqgComposerAudit\Application', 'mxr576\ddqgComposerAudit\Presentation'))
    //      ->because('The Infrastructure layer should only depend on the Domain layer and external namespaces - with some exceptions');

    //    $rules[] = Rule::allClasses()
    //      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Presentation'))
    //      ->should(new NotDependsOnTheseNamespaces('mxr576\ddqgComposerAudit\Domain', 'mxr576\ddqgComposerAudit\Infrastructure'))
    //      ->because('The Presentation layer should only depend on Application layer and external ones.');

    $config
      ->add($sourceFiles, ...$rules);
};
