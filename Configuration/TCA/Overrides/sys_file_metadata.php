<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addTCAcolumns('sys_file_metadata', [
    'tx_filenoindex_noindex' => [
        'exclude' => true,
        // The flag applies to the file itself, not to a translation of its metadata
        'l10n_mode' => 'exclude',
        'label' => 'LLL:EXT:file_noindex/Resources/Private/Language/locallang_db.xlf:sys_file_metadata.tx_filenoindex_noindex',
        'description' => 'LLL:EXT:file_noindex/Resources/Private/Language/locallang_db.xlf:sys_file_metadata.tx_filenoindex_noindex.description',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
        ],
    ],
]);

// Own "SEO" tab at the end of every type - does not collide with other
// extensions extending the metadata form and leaves room for future fields
ExtensionManagementUtility::addToAllTCAtypes(
    'sys_file_metadata',
    '--div--;LLL:EXT:file_noindex/Resources/Private/Language/locallang_db.xlf:sys_file_metadata.tab.seo,tx_filenoindex_noindex'
);
