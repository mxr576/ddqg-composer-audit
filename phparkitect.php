<?php

declare(strict_types=1);

/**
 * Copyright (c) 2023-2024 Dezső Biczó
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
      ->because('We want to protect our Domain');

    $rules[] = Rule::allClasses()
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Infrastructure'))
      ->should(new NotDependsOnTheseNamespaces('mxr576\ddqgComposerAudit\Application', 'mxr576\ddqgComposerAudit\Presentation', 'mxr576\ddqgComposerAudit\Supportive'))
      ->because('The Infrastructure layer should only depend on the Domain layer and on external namespaces. It is better if it does not depend on anything from Application. (See exceptions in baseline.)');

    $rules[] = Rule::allClasses()
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Presentation'))
      ->should(new NotDependsOnTheseNamespaces('mxr576\ddqgComposerAudit\Domain', 'mxr576\ddqgComposerAudit\Infrastructure'))
      ->because('The Presentation layer should only depend on the Application layer, stuff in Supportive and external namespaces.');

    $rules[] = Rule::allClasses()
      // Miscellaneous stuff that cannot go to any of these layers without breaking
      // the "outer layers can only depend on elements from the same layer or
      // inner layers" rule.
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Supportive'))
      ->should(new IsFinal())
      ->because('They are not part of the public API.');

    $rules[] = Rule::allClasses()
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Supportive'))
      ->should(new Arkitect\Expression\ForClasses\ContainDocBlockLike('@internal'))
      ->because('They are not part of the public API.');

    $config
      ->add($sourceFiles, ...$rules);
};
