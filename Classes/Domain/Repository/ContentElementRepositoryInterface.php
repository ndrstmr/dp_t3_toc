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
     * Find all top-level content elements for multiple pages.
     *
     * @param list<int> $pageUids List of page UIDs
     *
     * @return array<int, array<string, mixed>> Array of tt_content rows
     */
    public function findByPages(array $pageUids): array;

    /**
     * Find all children of a container element.
     *
     * @return array<int, array<string, mixed>> Array of tt_content rows
     */
    public function findContainerChildren(int $parentUid): array;

    /**
     * Find all container children for all elements on given pages (eager loading).
     *
     * This prevents N+1 query problems by loading all container children in a single query.
     *
     * @param list<int> $pageUids List of page UIDs
     *
     * @return array<int, array<string, mixed>> Array of tt_content rows with tx_container_parent set
     */
    public function findAllContainerChildrenForPages(array $pageUids): array;
}
