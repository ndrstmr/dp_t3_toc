<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Service;

use Ndrstmr\DpT3Toc\Domain\Model\TocItem;

interface TocBuilderServiceInterface
{
    /**
     * Build TOC from page content.
     *
     * @param array<int>|null $allowedColPos
     * @param array<int>|null $excludedColPos
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
    ): array;

    /**
     * Build TOC from multiple pages (with eager loading for performance).
     *
     * @param list<int>       $pageUids       List of page UIDs
     * @param array<int>|null $allowedColPos
     * @param array<int>|null $excludedColPos
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
