<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
        'single_quote' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
    ])
    ->setFinder(
        (new PhpCsFixer\Finder())
            ->in(__DIR__ . '/Classes')
            ->in(__DIR__ . '/Configuration')
            ->in(__DIR__ . '/Tests')
            ->name('*.php')
    );
