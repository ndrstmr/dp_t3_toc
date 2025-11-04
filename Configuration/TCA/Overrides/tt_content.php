<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Register new Content Element: menu_table_of_contents
// Using addRecordType() - the recommended method for content elements in TYPO3 v13+
// Automatically handles: SelectItem registration, TCA types configuration, icon registration
ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:dp_t3_toc/Resources/Private/Language/locallang_db.xlf:tt_content.CType.menu_table_of_contents',
        'value' => 'menu_table_of_contents',
        'icon' => 'content-table-of-contents',
        'group' => 'menu',
        'description' => 'LLL:EXT:dp_t3_toc/Resources/Private/Language/locallang_db.xlf:tt_content.CType.menu_table_of_contents.description',
    ],
    '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;;general,
            --palette--;;headers,
        --div--;LLL:EXT:dp_t3_toc/Resources/Private/Language/locallang_db.xlf:tt_content.tabs.toc_settings,
            pi_flexform,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
            --palette--;;frames,
            --palette--;;appearanceLinks,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
            --palette--;;language,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
            categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
            rowDescription,
    '
);

// Add FlexForm for additional settings
ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:dp_t3_toc/Configuration/FlexForms/TableOfContents.xml',
    'menu_table_of_contents'
);
