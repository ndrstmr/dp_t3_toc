<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Event;

use Ndrstmr\DpT3Toc\Domain\Model\TocItem;

/**
 * Event dispatched for each TOC item candidate.
 *
 * Allows filtering or modification of individual TOC items before they are added.
 */
final class TocItemFilterEvent
{
    /**
     * @param TocItem              $item   TOC item candidate
     * @param array<string, mixed> $rawRow Raw database row for advanced filtering
     * @param bool                 $skip   If true, item will be skipped
     */
    public function __construct(
        private TocItem $item,
        private readonly array $rawRow,
        private bool $skip = false,
    ) {
    }

    public function getItem(): TocItem
    {
        return $this->item;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawRow(): array
    {
        return $this->rawRow;
    }

    public function isSkipped(): bool
    {
        return $this->skip;
    }

    /**
     * Modify the TOC item.
     */
    public function setItem(TocItem $item): void
    {
        $this->item = $item;
    }

    /**
     * Mark this item to be skipped (filtered out).
     */
    public function skip(): void
    {
        $this->skip = true;
    }
}
