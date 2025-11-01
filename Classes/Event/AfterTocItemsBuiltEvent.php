<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Event;

use Ndrstmr\DpT3Toc\Domain\Model\TocConfiguration;
use Ndrstmr\DpT3Toc\Domain\Model\TocItem;

/**
 * Event dispatched after TOC items are built.
 *
 * Allows modification of final TOC items before they are returned.
 */
final class AfterTocItemsBuiltEvent
{
    /**
     * @param list<int>           $pageUids List of page UIDs that were processed
     * @param TocConfiguration    $config   TOC configuration used
     * @param array<int, TocItem> $items    Built TOC items
     */
    public function __construct(
        private readonly array $pageUids,
        private readonly TocConfiguration $config,
        private array $items,
    ) {
    }

    /**
     * @return list<int>
     */
    public function getPageUids(): array
    {
        return $this->pageUids;
    }

    public function getConfig(): TocConfiguration
    {
        return $this->config;
    }

    /**
     * @return array<int, TocItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Allow modification of TOC items.
     *
     * @param array<int, TocItem> $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}
