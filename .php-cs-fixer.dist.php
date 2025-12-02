<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude('vendor');

return (new Config())
    ->setRules([
        '@PSR12' => true,
        'binary_operator_spaces' => [
            'default'   => 'align_single_space_minimal',
            'operators' => ['=>' => 'align'], // RAPIH!
        ],
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'indentation_type' => true,
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true);
