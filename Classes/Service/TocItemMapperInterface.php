<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Service;

use Ndrstmr\DpT3Toc\Domain\Model\TocItem;

/**
 * Interface for mapping database rows to TocItem domain models.
 *
 * Responsibilities:
 * - Map content element data to TocItem Value Object
 * - Generate anchors (default #c{uid} or custom header_link)
 * - Ensure XSS-safe anchor generation
 */
interface TocItemMapperInterface
{
    /**
     * Map database row to TocItem domain model.
     *
     * @param array<string, mixed>       $row           Content element database row
     * @param int                        $level         TOC nesting level (0 = top-level)
     * @param list<array<string, mixed>> $path          Parent container path (for nested items)
     * @param bool                       $useHeaderLink Use header_link field for anchor (default: false)
     *
     * @return TocItem Mapped domain model with sanitized data
     */
    public function mapFromRow(array $row, int $level, array $path, bool $useHeaderLink = false): TocItem;
}
