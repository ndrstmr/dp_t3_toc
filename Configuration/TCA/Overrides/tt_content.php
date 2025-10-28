<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Register new Content Element: menu_table_of_contents
ExtensionManagementUtility::addTcaSelectItem(
    'tt_content',
    'CType',
    [
        'label' => 'LLL:EXT:dp_t3_toc/Resources/Private/Language/locallang_db.xlf:tt_content.CType.menu_table_of_contents',
        'value' => 'menu_table_of_contents',
        'icon' => 'content-table-of-contents',
        'group' => 'menu',
        'description' => 'LLL:EXT:dp_t3_toc/Resources/Private/Language/locallang_db.xlf:tt_content.CType.menu_table_of_contents.description',
    ]
);

// Configure fields for menu_table_of_contents
$GLOBALS['TCA']['tt_content']['types']['menu_table_of_contents'] = [
    'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;;general,
            --palette--;;headers,
            pages;LLL:EXT:dp_t3_toc/Resources/Private/Language/locallang_db.xlf:tt_content.pages.menu_table_of_contents,
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
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
    ',
    'columnsOverrides' => [
        'pages' => [
            'label' => 'LLL:EXT:dp_t3_toc/Resources/Private/Language/locallang_db.xlf:tt_content.pages.menu_table_of_contents',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 3,
                'maxitems' => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
    ],
];

// Add FlexForm for additional settings
ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:dp_t3_toc/Configuration/FlexForms/TableOfContents.xml',
    'menu_table_of_contents'
);
