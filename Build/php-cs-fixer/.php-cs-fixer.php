<?php

$rules = [
    '@Symfony' => true,
    'declare_strict_types' => true,
    'phpdoc_to_comment' => false,
    'ordered_imports' => true,
];

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder(
        PhpCsFixer\Finder::create()->in([__DIR__ . '/../../Classes', __DIR__ . '/../../Tests'])
    );
