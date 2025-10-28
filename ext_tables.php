<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Add static TypoScript template
ExtensionManagementUtility::addStaticFile(
    'dp_t3_toc',
    'Configuration/TypoScript',
    'Table of Contents'
);
