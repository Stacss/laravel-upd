<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude([
        'storage',
        'vendor',
        'bootstrap/cache',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => [
            'default' => 'align_single_space_minimal',
            'operators' => [
                '=>' => 'align_single_space_minimal',
                '='  => 'align_single_space_minimal',
            ],
        ],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'phpdoc_align' => ['align' => 'vertical'],
        'phpdoc_no_empty_return' => false,
        'phpdoc_to_comment' => false,
        'no_superfluous_phpdoc_tags' => false,
    ])
    ->setFinder($finder)
    ->setIndent("    ") // 4 пробела
    ->setLineEnding("\n");
