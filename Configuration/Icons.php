<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

/**
 * Icon registration for dp_t3_toc extension.
 *
 * This file is loaded automatically by TYPO3 v13+.
 * Icons registered here are available in the backend and frontend.
 */
return [
    'content-table-of-contents' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:dp_t3_toc/Resources/Public/Icons/content-table-of-contents.svg',
    ],
];
