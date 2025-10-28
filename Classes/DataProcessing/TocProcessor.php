<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\DataProcessing;

use Ndrstmr\DpT3Toc\Service\TocBuilderService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * DataProcessor for building a Table of Contents (TOC).
 *
 * Responsibilities:
 * - Parse TypoScript configuration
 * - Orchestrate TOC building via TocBuilderService
 * - Transform results to Fluid-compatible format
 *
 * All business logic is delegated to TocBuilderService (Single Responsibility)
 */
final readonly class TocProcessor implements DataProcessorInterface
{
    public function __construct(
        private TocBuilderService $tocBuilder,
    ) {
    }

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        // Parse configuration
        // Priority: 1. FlexForm (from tocSettings), 2. TypoScript, 3. Defaults
        $as = (string) ($processorConfiguration['as'] ?? 'tocItems');

        // Get FlexForm values if available (processed by FlexFormProcessor before this)
        $flexFormSettings = $processedData['tocSettings']['settings'] ?? [];

        // Mode: FlexForm > TypoScript > Default
        $mode = !empty($flexFormSettings['mode'])
            ? (string) $flexFormSettings['mode']
            : ($cObj->stdWrapValue('mode', $processorConfiguration) ?: 'visibleHeaders');

        // Include colPos: FlexForm > TypoScript > Default
        $includeColPos = !empty($flexFormSettings['includeColPos'])
            ? (string) $flexFormSettings['includeColPos']
            : ($cObj->stdWrapValue('includeColPos', $processorConfiguration) ?: '*');

        // Exclude colPos: FlexForm > TypoScript > Default
        $excludeColPos = !empty($flexFormSettings['excludeColPos'])
            ? (string) $flexFormSettings['excludeColPos']
            : ($cObj->stdWrapValue('excludeColPos', $processorConfiguration) ?: '');

        // MaxDepth: FlexForm > TypoScript > Default
        $maxDepth = isset($flexFormSettings['maxDepth']) && '' !== $flexFormSettings['maxDepth']
            ? (int) $flexFormSettings['maxDepth']
            : (int) ($cObj->stdWrapValue('maxDepth', $processorConfiguration) ?: '0');

        // Resolve page UID
        $pageUid = $this->resolvePageUid($cObj, $processorConfiguration);

        // Get current content element UID to exclude it from TOC
        $currentUid = (int) ($processedData['data']['uid'] ?? 0);

        // Parse colPos filters
        $allowedColPos = $this->normalizeColPosFilter($includeColPos);
        $excludedColPos = $this->normalizeColPosFilter($excludeColPos);

        // Build TOC using service (exclude current element)
        $tocItems = $this->tocBuilder->buildForPage($pageUid, $mode, $allowedColPos, $excludedColPos, $maxDepth, $currentUid);

        // Sort items
        $tocItems = $this->tocBuilder->sortItems($tocItems);

        // Transform to Fluid-compatible array format
        $processedData[$as] = array_map(fn ($item) => $item->toArray(), $tocItems);

        return $processedData;
    }

    /**
     * Parse colPos filter from configuration.
     *
     * @return array<int>|null Null if empty/wildcard, otherwise array of integers
     */
    private function normalizeColPosFilter(string $colPosFilter): ?array
    {
        // Trim whitespace
        $colPosFilter = trim($colPosFilter);

        // Empty string or wildcard = no filter
        if ('' === $colPosFilter || '*' === $colPosFilter) {
            return null;
        }

        // Parse comma-separated list
        $list = array_filter(
            array_map(trim(...), explode(',', $colPosFilter)),
            fn ($val) => '' !== $val
        );

        // Return null if no valid values
        if (empty($list)) {
            return null;
        }

        return array_map(intval(...), $list);
    }

    /**
     * Resolve the page UID from configuration or current page.
     */
    private function resolvePageUid(ContentObjectRenderer $cObj, array $processorConfiguration): int
    {
        // Use stdWrapValue to handle pidInList and pidInList.field properly
        $pageUid = $cObj->stdWrapValue('pidInList', $processorConfiguration);

        if ('' === $pageUid || 'this' === $pageUid) {
            return (int) ($GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.page.information')->getId() ?? 0);
        }

        return (int) $pageUid;
    }
}
