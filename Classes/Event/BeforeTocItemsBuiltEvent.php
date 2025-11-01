<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Event;

use Ndrstmr\DpT3Toc\Domain\Model\TocConfiguration;

/**
 * Event dispatched before TOC items are built.
 *
 * Allows modification of configuration or early termination of TOC building.
 */
final class BeforeTocItemsBuiltEvent
{
    /**
     * @param list<int>        $pageUids List of page UIDs to process
     * @param TocConfiguration $config   TOC configuration
     */
    public function __construct(
        private array $pageUids,
        private TocConfiguration $config,
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
     * Allow modification of page UIDs.
     *
     * @param list<int> $pageUids
     */
    public function setPageUids(array $pageUids): void
    {
        $this->pageUids = $pageUids;
    }

    /**
     * Allow modification of configuration.
     */
    public function setConfig(TocConfiguration $config): void
    {
        $this->config = $config;
    }
}
