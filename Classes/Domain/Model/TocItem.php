<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Domain\Model;

/**
 * Value Object representing a single TOC (Table of Contents) item.
 */
final readonly class TocItem
{
    /**
     * @param array<string, mixed>             $data Original tt_content row data
     * @param array<int, array<string, mixed>> $path Breadcrumb path from parent containers
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
        $colPos = (int) ($this->data['colPos'] ?? 0);

        // Container children (colPos >= 200): use parent's colPos
        if ($colPos >= 200 && !empty($this->path)) {
            // Use array_key_last to avoid modifying readonly property with end()
            $lastKey = array_key_last($this->path);
            $parent = $this->path[$lastKey];

            return (int) ($parent['colPos'] ?? $colPos);
        }

        return $colPos;
    }

    /**
     * Get the effective sorting (parent's sorting for container children).
     */
    public function getEffectiveSorting(): int
    {
        $sorting = (int) ($this->data['sorting'] ?? 0);
        $colPos = (int) ($this->data['colPos'] ?? 0);

        // Container children: use parent's sorting
        if ($colPos >= 200 && !empty($this->path)) {
            // Use array_key_last to avoid modifying readonly property with end()
            $lastKey = array_key_last($this->path);
            $parent = $this->path[$lastKey];

            return (int) ($parent['sorting'] ?? $sorting);
        }

        return $sorting;
    }

    /**
     * Convert to array format expected by Fluid templates.
     *
     * @return array<string, mixed>
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
