<?php

declare(strict_types=1);
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') or die();

// Register Icon
$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
$iconRegistry->registerIcon(
    'content-table-of-contents',
    SvgIconProvider::class,
    ['source' => 'EXT:dp_t3_toc/Resources/Public/Icons/content-table-of-contents.svg']
);
