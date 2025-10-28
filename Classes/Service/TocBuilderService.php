<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Service;

use Ndrstmr\DpT3Toc\Domain\Model\TocItem;
use Ndrstmr\DpT3Toc\Domain\Repository\ContentElementRepositoryInterface;

/**
 * Service for building Table of Contents from content elements.
 *
 * Handles container-aware recursive traversal and filtering logic
 */
final readonly class TocBuilderService implements TocBuilderServiceInterface
{
    public function __construct(
        private ContentElementRepositoryInterface $repository,
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
    ): array {
        $contentElements = $this->repository->findByPage($pageUid);
        $toc = [];

        foreach ($contentElements as $element) {
            // Skip the TOC element itself
            if ((int) ($element['uid'] ?? 0) === $excludeUid) {
                continue;
            }

            if ($this->isColPosAllowed($element, $allowedColPos, $excludedColPos)) {
                $this->collectRecursive($element, $toc, $mode, $maxDepth, 2, [], $allowedColPos, $excludedColPos, $excludeUid);
            }
        }

        return $toc;
    }

    /**
     * Recursively collect TOC items from content elements and their children.
     *
     * @param array<string, mixed>             $row            Content element data
     * @param array<int, TocItem>              $toc            TOC collection (passed by reference)
     * @param string                           $mode           Filter mode
     * @param int                              $maxDepth       Maximum depth
     * @param int                              $level          Current level
     * @param array<int, array<string, mixed>> $path           Parent path
     * @param array<int>|null                  $allowedColPos  Allowed column positions
     * @param array<int>|null                  $excludedColPos Excluded column positions
     * @param int                              $excludeUid     UID to exclude
     */
    private function collectRecursive(
        array $row,
        array &$toc,
        string $mode,
        int $maxDepth,
        int $level,
        array $path,
        ?array $allowedColPos = null,
        ?array $excludedColPos = null,
        int $excludeUid = 0,
    ): void {
        $ctype = (string) ($row['CType'] ?? '');
        $isContainer = isset($GLOBALS['TCA']['tt_content']['containerConfiguration'][$ctype]);

        // Add to TOC if it's a valid candidate
        if ($this->isCandidate($row, $mode)) {
            $toc[] = new TocItem(
                data: $row,
                title: (string) ($row['header'] ?? ''),
                anchor: '#c'.(int) $row['uid'],
                level: $level,
                path: $path
            );
        }

        // Recursively process container children
        if ($isContainer) {
            $children = $this->repository->findContainerChildren((int) $row['uid']);
            foreach ($children as $child) {
                // Skip excluded UID
                if ((int) ($child['uid'] ?? 0) === $excludeUid) {
                    continue;
                }

                // Check if child's colPos is allowed
                if ($this->isColPosAllowed($child, $allowedColPos, $excludedColPos)) {
                    $this->collectRecursive(
                        $child,
                        $toc,
                        $mode,
                        $maxDepth,
                        $maxDepth > 0 ? min($level + 1, $maxDepth) : $level + 1,
                        [...$path, [
                            'uid' => (int) $row['uid'],
                            'ctype' => $ctype,
                            'colPos' => (int) ($row['colPos'] ?? 0),
                            'sorting' => (int) ($row['sorting'] ?? 0),
                        ]],
                        $allowedColPos,
                        $excludedColPos,
                        $excludeUid
                    );
                }
            }
        }
    }

    /**
     * Check if content element is a valid TOC candidate based on mode.
     */
    private function isCandidate(array $row, string $mode): bool
    {
        $header = trim((string) ($row['header'] ?? ''));
        $headerLayout = (int) ($row['header_layout'] ?? 0); // 100 == hidden
        $sectionIndex = (int) ($row['sectionIndex'] ?? 0);

        return match ($mode) {
            'sectionIndexOnly' => 1 === $sectionIndex && '' !== $header,
            'visibleHeaders' => '' !== $header && 100 !== $headerLayout,
            'all' => '' !== $header,
            default => '' !== $header && 100 !== $headerLayout,
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
        $colPos = (int) ($row['colPos'] ?? 0);

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
     * @param array<int, TocItem> $items
     *
     * @return array<int, TocItem>
     */
    public function sortItems(array $items): array
    {
        usort($items, function (TocItem $a, TocItem $b) {
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
