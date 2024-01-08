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

use Ergebnis\License;

$license = License\Type\MIT::markdown(
    __DIR__ . '/LICENSE.md',
    License\Range::since(
        License\Year::fromString('2023'),
        new \DateTimeZone('UTC')
    ),
    License\Holder::fromString('Dezső Biczó'),
    License\Url::fromString('https://github.com/mxr576/ddqg-composer-audit/LICENSE.md')
);

$license->save();

$finder = PhpCsFixer\Finder::create()
  ->files()
  ->ignoreDotFiles(false)
  ->ignoreVCS(true)
  ->in(__DIR__)
  ->exclude(['tests/fixtures/e2e/web', 'tests/fixtures/e2e/vendor']);

$config = new PhpCsFixer\Config();
$config->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'class_definition' => ['single_line' => false, 'single_item_single_line' => true],
        'concat_space' => ['spacing' => 'one'],
        'declare_strict_types' => true,
        'ordered_class_elements' => true,
        'ordered_imports' => true,
        'phpdoc_align' => false,
        'phpdoc_annotation_without_dot' => false,
        'phpdoc_indent' => false,
        'phpdoc_inline_tag_normalizer' => false,
        'phpdoc_order' => true,
        'single_blank_line_at_eof' => true,
        'self_accessor' => false,
        'void_return' => true,
        'header_comment' => [
          'comment_type' => 'PHPDoc',
          'header' => $license->header(),
          'location' => 'after_declare_strict',
          'separate' => 'both',
        ],
    ])
    ->setFinder($finder);

return $config;
