<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Service;

use Ndrstmr\DpT3Toc\Domain\Model\TocItem;
use Ndrstmr\DpT3Toc\Domain\Repository\ContentElementRepositoryInterface;
use Ndrstmr\DpT3Toc\Utility\TypeCastingTrait;

/**
 * Service for building Table of Contents from content elements.
 *
 * Handles container-aware recursive traversal and filtering logic
 */
final readonly class TocBuilderService implements TocBuilderServiceInterface
{
    use TypeCastingTrait;

    public function __construct(
        private ContentElementRepositoryInterface $repository,
        private TcaContainerCheckServiceInterface $containerCheckService,
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
                // 1. Call the "pure function" and receive the returned array
                $collectedItems = $this->collectRecursive(
                    $element,
                    $mode,
                    $maxDepth,
                    $initialLevel,
                    $initialPath,
                    $allowedColPos,
                    $excludedColPos,
                    $excludeUid
                );

                // 2. Merge the results into the main TOC array
                if ([] !== $collectedItems) {
                    // Use array_push with spread operator (PHP 7.4+)
                    // This is more efficient in a loop than array_merge
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
    ): array {
        $collectedItems = [];
        $uid = $this->asInt($row['uid'] ?? 0);
        $ctype = $this->asString($row['CType'] ?? '');

        // 1. Add current element to TOC if it's a valid candidate
        if ($this->isCandidate($row, $mode)) {
            $collectedItems[] = new TocItem(
                data: $row,
                title: $this->asString($row['header'] ?? ''),
                anchor: '#c'.$uid,
                level: $level,
                path: $path
            );
        }

        $isContainer = $this->containerCheckService->isContainer($ctype);

        // --- Start Child Recursion ---

        // 2. Guard Clause: Stop recursion if maxDepth is set AND we've already reached it.
        //    (We only stop *descending*, the current item (above) is still added)
        if ($isContainer && $maxDepth > 0 && $level >= $maxDepth) {
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

            $children = $this->repository->findContainerChildren($uid);

            foreach ($children as $child) {
                // Skip excluded UID
                $childUid = $this->asInt($child['uid'] ?? 0);
                if ($childUid === $excludeUid) {
                    continue;
                }

                // Check if child's colPos is allowed
                if ($this->isColPosAllowed($child, $allowedColPos, $excludedColPos)) {
                    // Recursive call, returns an array (list<TocItem>)
                    $childItems = $this->collectRecursive(
                        $child,
                        $mode,
                        $maxDepth,
                        $level + 1, // Correct, simple level increment
                        $newPath,
                        $allowedColPos,
                        $excludedColPos,
                        $excludeUid
                    );

                    // 4. Merge results efficiently
                    if ([] !== $childItems) {
                        // Use array_push with spread operator (PHP 8.1+)
                        // This is more efficient in a loop than array_merge
                        array_push($collectedItems, ...$childItems);
                    }
                }
            }
        }

        return $collectedItems; // Return the collected items for this branch
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
        $headerLayout = $this->asInt($row['header_layout'] ?? 0); // 100 == hidden
        $sectionIndex = $this->asInt($row['sectionIndex'] ?? 0);

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
