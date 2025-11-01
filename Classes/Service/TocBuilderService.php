<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Service;

use Ndrstmr\DpT3Toc\Domain\Model\TocItem;
use Ndrstmr\DpT3Toc\Domain\Repository\ContentElementRepositoryInterface;
use Ndrstmr\DpT3Toc\Utility\TypeCastingTrait;
use Psr\Log\LoggerInterface;

/**
 * Service for building Table of Contents from content elements.
 *
 * Handles container-aware recursive traversal and filtering logic.
 *
 * Note: This class is NOT readonly to allow internal state management
 * for eager-loaded container children (prevents N+1 queries).
 */
final class TocBuilderService implements TocBuilderServiceInterface
{
    use TypeCastingTrait;

    /**
     * TYPO3 header_layout value for hidden headers.
     *
     * Headers with this layout value are considered hidden and excluded
     * from TOC in 'visibleHeaders' mode (default behavior).
     */
    private const HEADER_LAYOUT_HIDDEN = 100;

    /**
     * Eager-loaded container children grouped by parent UID.
     *
     * Format: ['parentUid' => [child1, child2, ...]]
     *
     * @var array<int, list<array<string, mixed>>>
     */
    private array $childrenByParent = [];

    public function __construct(
        private readonly ContentElementRepositoryInterface $repository,
        private readonly TcaContainerCheckServiceInterface $containerCheckService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Build TOC from page content.
     *
     * @param string          $mode           Filter mode: sectionIndexOnly|visibleHeaders|all
     * @param array<int>|null $allowedColPos  Allowed column positions (null = all)
     * @param array<int>|null $excludedColPos Excluded column positions (null = none)
     * @param int             $maxDepth       Maximum nesting depth (0 = unlimited)
     * @param int             $excludeUid     UID of content element to exclude (usually the TOC element itself)
     * @param bool            $useHeaderLink  Use header_link field if available (default: false)
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
    ): array {
        // Delegate to buildForPages with single page
        return $this->buildForPages([$pageUid], $mode, $allowedColPos, $excludedColPos, $maxDepth, $excludeUid, $useHeaderLink);
    }

    /**
     * Build TOC from multiple pages (solves N+1 query problem with eager loading).
     *
     * @param list<int>       $pageUids       List of page UIDs
     * @param string          $mode           Filter mode: sectionIndexOnly|visibleHeaders|all
     * @param array<int>|null $allowedColPos  Allowed column positions (null = all)
     * @param array<int>|null $excludedColPos Excluded column positions (null = none)
     * @param int             $maxDepth       Maximum nesting depth (0 = unlimited)
     * @param int             $excludeUid     UID of content element to exclude (usually the TOC element itself)
     * @param bool            $useHeaderLink  Use header_link field if available (default: false)
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
    ): array {
        // Reset state for new TOC building
        $this->childrenByParent = [];

        // Early return for empty input
        if ([] === $pageUids) {
            return [];
        }

        // 1. Load all top-level elements from all pages (1 query)
        $contentElements = $this->repository->findByPages($pageUids);

        $this->logger->debug('TOC Service: Loaded top-level content elements', [
            'pageUids' => $pageUids,
            'elementCount' => count($contentElements),
        ]);

        // 2. Eager-load ALL container children at once (1 query, prevents N+1 problem!)
        $allChildren = $this->repository->findAllContainerChildrenForPages($pageUids);

        $this->logger->debug('TOC Service: Eager-loaded container children', [
            'pageUids' => $pageUids,
            'childrenCount' => count($allChildren),
        ]);

        // 3. Group children by parent UID for O(1) lookup during recursion
        foreach ($allChildren as $child) {
            $parentUid = $this->asInt($child['tx_container_parent'] ?? 0);
            if ($parentUid > 0) {
                $this->childrenByParent[$parentUid][] = $child;
            }
        }

        // 4. Process all elements recursively (using in-memory children)
        $toc = [];

        // Define initial parameters for clarity (avoids "Magic Numbers")
        $initialLevel = 2; // We assume page title = level 1, content starts at level 2
        $initialPath = []; // The root elements have no parent path

        foreach ($contentElements as $element) {
            // Skip the TOC element itself
            $uid = $element['uid'] ?? null;
            if (is_numeric($uid) && (int) $uid === $excludeUid) {
                continue;
            }

            // Check colPos *before* descending into recursion
            if ($this->isColPosAllowed($element, $allowedColPos, $excludedColPos)) {
                // Call the recursive function
                $collectedItems = $this->collectRecursive(
                    $element,
                    $mode,
                    $maxDepth,
                    $initialLevel,
                    $initialPath,
                    $allowedColPos,
                    $excludedColPos,
                    $excludeUid,
                    $useHeaderLink
                );

                // Merge the results into the main TOC array
                if ([] !== $collectedItems) {
                    array_push($toc, ...$collectedItems);
                }
            }
        }

        return $toc;
    }

    /**
     * Recursively collect TOC items from content elements and their children.
     *
     * @param array<string, mixed>       $row            Content element data
     * @param string                     $mode           Filter mode
     * @param int                        $maxDepth       Maximum depth (0 = unlimited)
     * @param int                        $level          Current level
     * @param list<array<string, mixed>> $path           Parent path (dies 'list' ist OK)
     * @param array<int>|null            $allowedColPos  Allowed column positions
     * @param array<int>|null            $excludedColPos Excluded column positions
     * @param int                        $excludeUid     UID to exclude
     * @param bool                       $useHeaderLink  Use header_link field as anchor if available
     *
     * @return array<int, TocItem> A list of TocItem objects
     */
    private function collectRecursive(
        array $row,
        string $mode,
        int $maxDepth,
        int $level,
        array $path,
        ?array $allowedColPos = null,
        ?array $excludedColPos = null,
        int $excludeUid = 0,
        bool $useHeaderLink = false,
    ): array {
        $collectedItems = [];
        $uid = $this->asInt($row['uid'] ?? 0);
        $ctype = $this->asString($row['CType'] ?? '');

        // 1. Add current element to TOC if it's a valid candidate
        if ($this->isCandidate($row, $mode)) {
            // Auto-anchor generation: Use header_link if enabled and available
            $headerLink = trim($this->asString($row['header_link'] ?? ''));
            $anchor = ($useHeaderLink && '' !== $headerLink)
                ? $this->sanitizeAnchor($headerLink, $uid)
                : '#c'.$uid;

            $collectedItems[] = new TocItem(
                data: $row,
                title: $this->asString($row['header'] ?? ''),
                anchor: $anchor,
                level: $level,
                path: $path
            );
        }

        $isContainer = $this->containerCheckService->isContainer($ctype);

        // --- Start Child Recursion ---

        // 2. Guard Clause: Stop recursion if maxDepth is set AND we've already reached it.
        //    (We only stop *descending*, the current item (above) is still added)
        if ($isContainer && $maxDepth > 0 && $level >= $maxDepth) {
            $this->logger->debug('TOC Service: Max depth reached, stopping recursion', [
                'uid' => $uid,
                'ctype' => $ctype,
                'currentLevel' => $level,
                'maxDepth' => $maxDepth,
            ]);

            return $collectedItems; // Stop descending
        }

        // 3. Process children
        if ($isContainer) {
            // Prepare new path for all children of this container
            $newPath = [...$path, [
                'uid' => $uid,
                'ctype' => $ctype,
                'colPos' => $this->asInt($row['colPos'] ?? 0),
                'sorting' => $this->asInt($row['sorting'] ?? 0),
            ]];

            // Use eager-loaded children (no DB query!) - solves N+1 problem
            // Default to empty array if no children exist for this parent
            $children = $this->childrenByParent[$uid] ?? [];

            foreach ($children as $child) {
                // Skip excluded UID
                $childUid = $this->asInt($child['uid'] ?? 0);
                if ($childUid === $excludeUid) {
                    continue;
                }

                // Container children inherit the parent's colPos visibility
                // Their internal colPos values (200, 201, etc.) are b13/container
                // implementation details and should NOT be filtered.
                // The parent container was already checked by isColPosAllowed().
                $childItems = $this->collectRecursive(
                    $child,
                    $mode,
                    $maxDepth,
                    $level + 1,
                    $newPath,
                    $allowedColPos,
                    $excludedColPos,
                    $excludeUid,
                    $useHeaderLink
                );

                // Merge results efficiently
                if ([] !== $childItems) {
                    array_push($collectedItems, ...$childItems);
                }
            }
        }

        return $collectedItems; // Return the collected items for this branch
    }

    /**
     * Sanitize and validate anchor string from header_link field.
     *
     * Validates against regex ^[a-zA-Z0-9_-]+$ to prevent XSS attacks.
     * Falls back to #c{uid} if validation fails.
     *
     * @param string $headerLink User-provided anchor from header_link field
     * @param int    $uid        Content element UID for fallback
     *
     * @return string Validated anchor with # prefix, or fallback #c{uid}
     */
    private function sanitizeAnchor(string $headerLink, int $uid): string
    {
        // Validate: Only allow alphanumeric, underscore, and hyphen
        // This prevents XSS attacks via malicious anchor strings
        if (1 === preg_match('/^[a-zA-Z0-9_-]+$/', $headerLink)) {
            return '#'.$headerLink;
        }

        // Invalid anchor: fall back to default #c{uid} format
        return '#c'.$uid;
    }

    /**
     * Check if content element is a valid TOC candidate based on mode.
     *
     * @param array<string, mixed> $row  Content element data
     * @param string               $mode Filter mode
     */
    private function isCandidate(array $row, string $mode): bool
    {
        $header = trim($this->asString($row['header'] ?? ''));
        $headerLayout = $this->asInt($row['header_layout'] ?? 0);
        $sectionIndex = $this->asInt($row['sectionIndex'] ?? 0);

        return match ($mode) {
            'sectionIndexOnly' => 1 === $sectionIndex && '' !== $header,
            'visibleHeaders' => '' !== $header && self::HEADER_LAYOUT_HIDDEN !== $headerLayout,
            'all' => '' !== $header,
            default => '' !== $header && self::HEADER_LAYOUT_HIDDEN !== $headerLayout,
        };
    }

    /**
     * Check if colPos is allowed (considering both include and exclude filters).
     *
     * @param array<string, mixed> $row            Content element
     * @param array<int>|null      $allowedColPos  Whitelist of allowed colPos values
     * @param array<int>|null      $excludedColPos Blacklist of excluded colPos values
     */
    private function isColPosAllowed(array $row, ?array $allowedColPos, ?array $excludedColPos): bool
    {
        $colPos = $this->asInt($row['colPos'] ?? 0);

        // Check exclude list first (blacklist takes precedence)
        if (null !== $excludedColPos && in_array($colPos, $excludedColPos, true)) {
            return false;
        }

        // Check include list (whitelist)
        if (null !== $allowedColPos) {
            return in_array($colPos, $allowedColPos, true);
        }

        // No filters: allow all
        return true;
    }

    /**
     * Sort TOC items by colPos and sorting.
     *
     * @param array<TocItem> $items
     *
     * @return list<TocItem>
     */
    public function sortItems(array $items): array
    {
        // Use 'static fn' as $this is not accessed (PHPStan/Performance Best Practice)
        usort($items, static function (TocItem $a, TocItem $b): int {
            $colPosA = $a->getEffectiveColPos();
            $colPosB = $b->getEffectiveColPos();

            // First by effective colPos
            if ($colPosA !== $colPosB) {
                return $colPosA <=> $colPosB;
            }

            // Then by effective sorting
            return $a->getEffectiveSorting() <=> $b->getEffectiveSorting();
        });

        return $items;
    }
}
