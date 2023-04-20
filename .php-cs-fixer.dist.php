<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
  ->files()
  ->in([__DIR__ . '/src']);

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
    ])
    ->setFinder($finder);

return $config;
