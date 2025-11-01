<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Service;

use Ndrstmr\DpT3Toc\Domain\Model\TocConfiguration;
use Ndrstmr\DpT3Toc\Domain\Model\TocItem;

interface TocBuilderServiceInterface
{
    /**
     * Build TOC from page content with configuration object.
     *
     * @return array<int, TocItem>
     */
    public function buildForPageWithConfig(int $pageUid, TocConfiguration $config): array;

    /**
     * Build TOC from multiple pages with configuration object (preferred method).
     *
     * @param list<int> $pageUids List of page UIDs
     *
     * @return array<int, TocItem>
     */
    public function buildForPagesWithConfig(array $pageUids, TocConfiguration $config): array;

    /**
     * Build TOC from page content.
     *
     * @deprecated Use buildForPageWithConfig() instead. Will be removed in v5.0.
     *
     * @param array<int>|null $allowedColPos
     * @param array<int>|null $excludedColPos
     * @param bool            $useHeaderLink  Use header_link field if available
     *
     * @return array<int, TocItem>
     */
    public function buildForPage(
        int $pageUid,
        string $mode = 'visibleHeaders',
        ?array $allowedColPos = null,
        ?array $excludedColPos = null,
        int $maxDepth = 0,
        int $excludeUid = 0,
        bool $useHeaderLink = false,
    ): array;

    /**
     * Build TOC from multiple pages (with eager loading for performance).
     *
     * @deprecated Use buildForPagesWithConfig() instead. Will be removed in v5.0.
     *
     * @param list<int>       $pageUids       List of page UIDs
     * @param array<int>|null $allowedColPos
     * @param array<int>|null $excludedColPos
     * @param bool            $useHeaderLink  Use header_link field if available
     *
     * @return array<int, TocItem>
     */
    public function buildForPages(
        array $pageUids,
        string $mode = 'visibleHeaders',
        ?array $allowedColPos = null,
        ?array $excludedColPos = null,
        int $maxDepth = 0,
        int $excludeUid = 0,
        bool $useHeaderLink = false,
    ): array;

    /**
     * Sort TOC items by colPos and sorting.
     *
     * @param array<int, TocItem> $items
     *
     * @return array<int, TocItem>
     */
    public function sortItems(array $items): array;
}
