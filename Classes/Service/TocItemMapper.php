<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Service;

use Ndrstmr\DpT3Toc\Domain\Model\TocItem;
use Ndrstmr\DpT3Toc\Utility\TypeCastingTrait;

/**
 * Mapper service for creating TocItem objects from database rows.
 *
 * Handles:
 * - TocItem creation with type-safe data extraction
 * - Anchor generation (default #c{uid} or custom header_link)
 * - XSS prevention via anchor validation
 *
 * Single Responsibility: Mapping data → Domain Model
 */
final readonly class TocItemMapper implements TocItemMapperInterface
{
    use TypeCastingTrait;

    /**
     * Map database row to TocItem domain model.
     *
     * @param array<string, mixed>       $row           Content element database row
     * @param int                        $level         TOC nesting level
     * @param list<array<string, mixed>> $path          Parent container path
     * @param bool                       $useHeaderLink Use header_link field for anchor (default: false)
     *
     * @return TocItem Mapped domain model
     */
    public function mapFromRow(array $row, int $level, array $path, bool $useHeaderLink = false): TocItem
    {
        $uid = $this->asInt($row['uid'] ?? 0);
        $title = trim($this->asString($row['header'] ?? ''));

        // Anchor generation: Use header_link if enabled and available
        $anchor = $this->generateAnchor($row, $uid, $useHeaderLink);

        return new TocItem(
            data: $row,
            title: $title,
            anchor: $anchor,
            level: $level,
            path: $path
        );
    }

    /**
     * Generate anchor string for TocItem.
     *
     * Priority:
     * 1. Use header_link field if enabled and valid
     * 2. Fall back to default #c{uid} format
     *
     * @param array<string, mixed> $row           Content element data
     * @param int                  $uid           Content element UID
     * @param bool                 $useHeaderLink Enable header_link usage
     *
     * @return string Anchor string with # prefix
     */
    private function generateAnchor(array $row, int $uid, bool $useHeaderLink): string
    {
        if ($useHeaderLink) {
            $headerLink = trim($this->asString($row['header_link'] ?? ''));
            if ('' !== $headerLink) {
                return $this->sanitizeAnchor($headerLink, $uid);
            }
        }

        // Default anchor format
        return '#c'.$uid;
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
}
