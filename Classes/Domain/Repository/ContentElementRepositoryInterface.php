<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Domain\Repository;

/**
 * Repository interface for fetching content elements.
 *
 * Abstracts data access to allow different implementations (DB, cache, etc.)
 */
interface ContentElementRepositoryInterface
{
    /**
     * Find all top-level content elements for a given page.
     *
     * @return array<int, array<string, mixed>> Array of tt_content rows
     */
    public function findByPage(int $pageUid): array;

    /**
     * Find all children of a container element.
     *
     * @return array<int, array<string, mixed>> Array of tt_content rows
     */
    public function findContainerChildren(int $parentUid): array;
}
