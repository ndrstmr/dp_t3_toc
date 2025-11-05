<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Container-Aware TOC DataProcessor',
    'description' => 'Ready-to-use Table of Contents Content Element for TYPO3 v13 with Bootstrap 5, Kern UX, and full b13/container support. 3 layouts (sidebar/inline/compact), FlexForm config, WCAG 2.1 AA, Clean Architecture, zero dependencies.',
    'category' => 'fe',
    'author' => 'Andreas Teumer',
    'author_email' => 'aeaeue@ndrstmr.de',
    'state' => 'stable',
    'version' => '4.3.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.99.99',
            'php' => '8.3.0-8.99.99'
        ],
        'suggests' => [
            'container' => '',
            'bootstrap_package' => ''
        ],
    ],
];
