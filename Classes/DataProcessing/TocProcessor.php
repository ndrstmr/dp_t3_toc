<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\DataProcessing;

use Ndrstmr\DpT3Toc\Domain\Model\TocItem;
use Ndrstmr\DpT3Toc\Service\TocBuilderServiceInterface;
use Ndrstmr\DpT3Toc\Utility\TypeCastingTrait;
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

        // Resolve page UID
        /** @var array<string, mixed> $processorConfiguration */
        $pageUid = $this->resolvePageUid($cObj, $processorConfiguration);

        // Get current content element UID to exclude it from TOC
        $data = $this->asArray($processedData['data'] ?? null);
        $currentUid = $this->asInt($data['uid'] ?? 0);

        // Parse colPos filters
        $allowedColPos = $this->normalizeColPosFilter($includeColPos);
        $excludedColPos = $this->normalizeColPosFilter($excludeColPos);

        // Build TOC using service (exclude current element)
        $tocItems = $this->tocBuilder->buildForPage($pageUid, $mode, $allowedColPos, $excludedColPos, $maxDepth, $currentUid);

        // Sort items
        $tocItems = $this->tocBuilder->sortItems($tocItems);

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
     * Resolve the page UID from configuration or current page.
     *
     * @param array<string, mixed> $processorConfiguration
     */
    private function resolvePageUid(ContentObjectRenderer $cObj, array $processorConfiguration): int
    {
        // stdWrapValue returns mixed, casting to string is safe for comparison
        $pageUid = (string) $cObj->stdWrapValue('pidInList', $processorConfiguration);

        if ('' === $pageUid || 'this' === $pageUid) {
            // getRequest() returns ServerRequest (non-nullable)
            $request = $cObj->getRequest();

            $pageInformation = $request->getAttribute('frontend.page.information');

            // This is the crucial check for PHPStan max level
            if (!$pageInformation instanceof PageInformation) {
                // Attribute is not set or has an unexpected type
                return 0;
            }

            // After this check, PHPStan knows $pageInformation is PageInformation
            // PageInformation::getId() returns int|null
            return $pageInformation->getId();
        }

        return (int) $pageUid;
    }
}
