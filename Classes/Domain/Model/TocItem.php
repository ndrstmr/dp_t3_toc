<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Domain\Model;

use Ndrstmr\DpT3Toc\Utility\TypeCastingTrait;

/**
 * Value Object representing a single TOC (Table of Contents) item.
 */
final readonly class TocItem
{
    use TypeCastingTrait;

    /**
     * @param array<string, mixed>       $data Original tt_content row data
     * @param list<array<string, mixed>> $path Breadcrumb path from parent containers
     */
    public function __construct(
        public array $data,
        public string $title,
        public string $anchor,
        public int $level,
        public array $path = [],
    ) {
    }

    /**
     * Get the effective colPos (parent's colPos for container children).
     */
    public function getEffectiveColPos(): int
    {
        $colPos = $this->asInt($this->data['colPos'] ?? 0);

        // Container children (colPos >= 200): use parent's colPos
        if ($colPos >= 200 && [] !== $this->path) {
            $lastKey = array_key_last($this->path);

            $parent = $this->path[$lastKey];

            return $this->asInt($parent['colPos'] ?? $colPos);
        }

        return $colPos;
    }

    /**
     * Get the effective sorting (element's own sorting).
     */
    public function getEffectiveSorting(): int
    {
        // This MUST return the element's OWN sorting.
        return $this->asInt($this->data['sorting'] ?? 0);
    }

    /**
     * Convert to array format expected by Fluid templates.
     *
     * @return array{
     * data: array<string, mixed>,
     * title: string,
     * anchor: string,
     * level: int,
     * path: list<array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'title' => $this->title,
            'anchor' => $this->anchor,
            'level' => $this->level,
            'path' => $this->path,
        ];
    }
}
