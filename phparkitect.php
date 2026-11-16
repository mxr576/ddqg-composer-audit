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

use Arkitect\ClassSet;
use Arkitect\CLI\Config;
use Arkitect\Expression\ForClasses\ContainDocBlockLike;
use Arkitect\Expression\ForClasses\IsFinal;
use Arkitect\Expression\ForClasses\IsNotEnum;
use Arkitect\Expression\ForClasses\IsNotInterface;
use Arkitect\Expression\ForClasses\IsNotTrait;
use Arkitect\Expression\ForClasses\NotDependsOnTheseNamespaces;
use Arkitect\Expression\ForClasses\NotHaveDependencyOutsideNamespace;
use Arkitect\Expression\ForClasses\ResideInOneOfTheseNamespaces;
use Arkitect\Rules\Rule;

return static function (Config $config): void {
    $sourceFiles = ClassSet::fromDir(__DIR__ . '/src');

    $rules = [];

    $rules[] = Rule::allClasses()
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit'))
      ->should(new ResideInOneOfTheseNamespaces(
          'mxr576\ddqgComposerAudit\Domain',
          'mxr576\ddqgComposerAudit\Application',
          'mxr576\ddqgComposerAudit\Infrastructure',
          'mxr576\ddqgComposerAudit\Presentation',
      ))
      ->because('Every class belongs to exactly one layer, so the dependency rules below cover all of src/.');

    $rules[] = Rule::allClasses()
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Domain'))
      ->should(new NotHaveDependencyOutsideNamespace('mxr576\ddqgComposerAudit\Domain', [
          'RuntimeException',
          'DateTimeImmutable',
          'Traversable',
          'ArrayObject',
          // We have to handle some Composer stuff as part of our domain, we do not
          // want to redevelop them in the scope of a Composer plugin.
          'Composer\Package\PackageInterface',
          'Composer\Advisory\SecurityAdvisory',
          'Composer\Semver\VersionParser',
          'Composer\Semver\Constraint\ConstraintInterface',
          'Composer\Semver\Constraint\Constraint',
      ]))
      ->because('We want to protect the Domain');

    $rules[] = Rule::allClasses()
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Domain'))
      ->andThat(new IsNotInterface())
      ->andThat(new IsNotEnum())
      ->andThat(new IsNotTrait())
      ->should(new IsFinal())
      ->because('We want to protect our Domain');

    $rules[] = Rule::allClasses()
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Application'))
      ->should(new NotDependsOnTheseNamespaces(['mxr576\ddqgComposerAudit\Infrastructure', 'mxr576\ddqgComposerAudit\Presentation']))
      ->because('The Application layer should only depend on the Domain layer and on external namespaces.');

    $rules[] = Rule::allClasses()
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Infrastructure'))
      ->should(new NotDependsOnTheseNamespaces(['mxr576\ddqgComposerAudit\Application', 'mxr576\ddqgComposerAudit\Presentation']))
      ->because('The Infrastructure layer should only depend on the Domain layer and on external namespaces.');

    // Presentation is the composition root: it may depend on any layer. Nothing
    // else may depend on it, which the Domain allow-list and the two rules above
    // enforce.
    $rules[] = Rule::allClasses()
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Presentation'))
      ->should(new IsFinal())
      ->because('They are not part of the public API.');

    $rules[] = Rule::allClasses()
      ->that(new ResideInOneOfTheseNamespaces('mxr576\ddqgComposerAudit\Presentation'))
      ->should(new ContainDocBlockLike('@internal'))
      ->because('They are not part of the public API.');

    $config
      ->add($sourceFiles, ...$rules);
};
