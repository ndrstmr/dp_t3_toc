<?php

declare(strict_types=1);

/**
 * JavaScript ES6 Module Configuration for dp_t3_toc
 *
 * Registers import map for ES6 modules to enable:
 * - Dynamic imports (lazy loading)
 * - Node.js-style path resolution
 * - Modern JavaScript without build tools
 *
 * @see https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Feature-96510-InfrastructureForJavaScriptModulesAndImportmaps.html
 */
return [
    // Dependencies: empty (no other extension JS modules required)
    'dependencies' => [],

    // Import map: Register namespace for ES6 module imports
    // Usage in JavaScript: import { initSticky } from '@ndrstmr/dp-t3-toc/toc-sticky.js';
    'imports' => [
        '@ndrstmr/dp-t3-toc/' => 'EXT:dp_t3_toc/Resources/Public/JavaScript/',
    ],
];
