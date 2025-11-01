<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\DataProcessing;

use Ndrstmr\DpT3Toc\Domain\Model\TocItem;
use Ndrstmr\DpT3Toc\Service\TocBuilderServiceInterface;
use Ndrstmr\DpT3Toc\Utility\TypeCastingTrait;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Page\PageInformation;

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
    use TypeCastingTrait;

    public function __construct(
        private TocBuilderServiceInterface $tocBuilder,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param ContentObjectRenderer $cObj                       The ContentObjectRenderer
     * @param array<mixed>          $contentObjectConfiguration The configuration of this CObject
     * @param array<mixed>          $processorConfiguration     The configuration of this DataProcessor
     * @param array<mixed>          $processedData              The processed data from previous DataProcessors
     *
     * @return array<mixed> The final processed data
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        // Parse configuration
        // Priority: 1. FlexForm (from tocSettings), 2. TypoScript, 3. Defaults
        $as = $this->asString($processorConfiguration['as'] ?? 'tocItems');

        $tocSettings = $this->asArray($processedData['tocSettings'] ?? null);
        $flexFormSettings = $this->asArray($tocSettings['settings'] ?? []);

        // Mode: FlexForm > TypoScript > Default
        $tsMode = $this->asString($cObj->stdWrapValue('mode', $processorConfiguration));
        $mode = (isset($flexFormSettings['mode']) && '' !== $flexFormSettings['mode'])
            ? $this->asString($flexFormSettings['mode'])
            : ('' !== $tsMode ? $tsMode : 'visibleHeaders');

        // Include colPos: FlexForm > TypoScript > Default
        $tsIncludeColPos = $this->asString($cObj->stdWrapValue('includeColPos', $processorConfiguration));
        $includeColPos = (isset($flexFormSettings['includeColPos']) && '' !== $flexFormSettings['includeColPos'])
            ? $this->asString($flexFormSettings['includeColPos'])
            : ('' !== $tsIncludeColPos ? $tsIncludeColPos : '*');

        // Exclude colPos: FlexForm > TypoScript > Default
        $tsExcludeColPos = $this->asString($cObj->stdWrapValue('excludeColPos', $processorConfiguration));
        $excludeColPos = (isset($flexFormSettings['excludeColPos']) && '' !== $flexFormSettings['excludeColPos'])
            ? $this->asString($flexFormSettings['excludeColPos'])
            : ('' !== $tsExcludeColPos ? $tsExcludeColPos : '');

        // MaxDepth: FlexForm > TypoScript > Default
        $tsMaxDepth = $cObj->stdWrapValue('maxDepth', $processorConfiguration);
        $maxDepth = (isset($flexFormSettings['maxDepth']) && '' !== $flexFormSettings['maxDepth'])
            ? $this->asInt($flexFormSettings['maxDepth'])
            : $this->asInt('' !== $this->asString($tsMaxDepth) ? $tsMaxDepth : '0');

        // Use header_link as anchor: Site Setting (no FlexForm override)
        $useHeaderLink = (bool) ($tocSettings['useHeaderLinkAsAnchor'] ?? false);

        // Resolve page UIDs (supports CSV: "1,2,3")
        /** @var array<string, mixed> $processorConfiguration */
        /** @var array<string, mixed> $processedData */
        $pageUids = $this->resolvePageUids($cObj, $processorConfiguration, $processedData);

        // Log invalid/empty page UIDs
        if ([] === $pageUids || [0] === $pageUids) {
            $this->logger->warning('TOC: No valid page UIDs resolved', [
                'pidInList' => $processorConfiguration['pidInList'] ?? 'not set',
                'fallbackAttempted' => true,
            ]);
        }

        // Get current content element UID to exclude it from TOC
        $data = $this->asArray($processedData['data'] ?? null);
        $currentUid = $this->asInt($data['uid'] ?? 0);

        // Parse colPos filters
        $allowedColPos = $this->normalizeColPosFilter($includeColPos);
        $excludedColPos = $this->normalizeColPosFilter($excludeColPos);

        // Log configuration (debug level)
        $this->logger->debug('TOC: Building table of contents', [
            'pageUids' => $pageUids,
            'mode' => $mode,
            'allowedColPos' => $allowedColPos,
            'excludedColPos' => $excludedColPos,
            'maxDepth' => $maxDepth,
            'excludeUid' => $currentUid,
            'useHeaderLink' => $useHeaderLink,
        ]);

        // Build TOC using service (exclude current element)
        // Uses buildForPages() which supports multi-page and eager loading
        $tocItems = $this->tocBuilder->buildForPages($pageUids, $mode, $allowedColPos, $excludedColPos, $maxDepth, $currentUid, $useHeaderLink);

        // Log empty results (info level - not an error, but worth knowing)
        if ([] === $tocItems) {
            $this->logger->info('TOC: No items found', [
                'pageUids' => $pageUids,
                'mode' => $mode,
                'filters' => [
                    'allowedColPos' => $allowedColPos,
                    'excludedColPos' => $excludedColPos,
                ],
            ]);
        }

        // Sort items
        $tocItems = $this->tocBuilder->sortItems($tocItems);

        // Log successful build (debug level)
        $this->logger->debug('TOC: Successfully built', [
            'itemCount' => count($tocItems),
            'pageUids' => $pageUids,
        ]);

        // Transform to Fluid-compatible array format
        $processedData[$as] = array_map(
            static fn (TocItem $item): array => $item->toArray(),
            $tocItems
        );

        return $processedData;
    }

    /**
     * Parse colPos filter from configuration.
     *
     * @param string $colPosFilter The raw colPos string (e.g., "1,2,3" or "*")
     *
     * @return list<int>|null Null if empty/wildcard, otherwise a list of integers
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
            // Filter out empty values (e.g., from "1,,2")
            static fn (string $val): bool => '' !== $val
        );

        // Return null if no valid values
        if ([] === $list) {
            return null;
        }

        $intList = array_map(intval(...), $list);
        // $intList is e.g., [0 => 1, 2 => 3]

        // Reset keys to ensure a 'list' (0-indexed, non-sparse)
        // This makes [0 => 1, 2 => 3] into [0 => 1, 1 => 3]
        return array_values($intList);
    }

    /**
     * Resolve page UIDs from configuration or current page with fallback chain.
     *
     * Supports:
     * - Single UID: "42"
     * - Multiple UIDs (CSV): "42,43,44"
     * - Current page: "this" or ""
     *
     * Fallback chain for "this":
     * 1. PSR-7 Request attribute 'frontend.page.information' (TYPO3 v13+, standard frontend)
     * 2. Content element's 'pid' from $processedData['data']['pid'] (always available in DataProcessor)
     *
     * @param array<string, mixed> $processorConfiguration
     * @param array<string, mixed> $processedData
     *
     * @return list<int> List of page UIDs (never empty, defaults to [0] on error)
     */
    private function resolvePageUids(ContentObjectRenderer $cObj, array $processorConfiguration, array $processedData): array
    {
        // stdWrapValue returns mixed, use TypeCastingTrait for safe conversion
        $pidInListRaw = $cObj->stdWrapValue('pidInList', $processorConfiguration);
        $pidInList = $this->asString($pidInListRaw);

        // Handle "this" or empty string → use current page with fallback chain
        if ('' === $pidInList || 'this' === $pidInList) {
            // Fallback 1: PSR-7 Request attribute (modern, TYPO3 v13+)
            $request = $cObj->getRequest();
            $pageInformation = $request->getAttribute('frontend.page.information');

            if ($pageInformation instanceof PageInformation) {
                $currentPageUid = $pageInformation->getId();
                if ($currentPageUid > 0) {
                    return [$currentPageUid];
                }
            }

            // Fallback 2: Content element's pid field (robust, always available in DataProcessor context)
            $data = $this->asArray($processedData['data'] ?? null);
            $contentElementPid = $this->asInt($data['pid'] ?? 0);
            if ($contentElementPid > 0) {
                return [$contentElementPid];
            }

            // All fallbacks failed: return [0] (will result in empty TOC)
            // This should only happen in edge cases (e.g., CLI context without proper data)
            return [0];
        }

        // Parse CSV: "42,43,44" → [42, 43, 44]
        $parts = explode(',', $pidInList);

        // Use TypeCastingTrait and filter out invalid values
        $validUids = [];
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ('' !== $trimmed) {
                $uid = $this->asInt($trimmed);
                if ($uid > 0) {
                    $validUids[] = $uid;
                }
            }
        }

        // Fallback: If no valid UIDs found, return [0]
        if ([] === $validUids) {
            return [0];
        }

        return $validUids;
    }
}
