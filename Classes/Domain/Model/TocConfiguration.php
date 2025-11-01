<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Domain\Model;

use Ndrstmr\DpT3Toc\Utility\TypeCastingTrait;

/**
 * Value Object for TOC configuration parameters.
 *
 * Replaces multiple method parameters with a single, immutable configuration object.
 * Improves readability, maintainability, and allows easy extension of configuration options.
 */
final readonly class TocConfiguration
{
    use TypeCastingTrait;

    /**
     * @param string          $mode           Filter mode: sectionIndexOnly|visibleHeaders|all
     * @param array<int>|null $allowedColPos  Allowed column positions (null = all)
     * @param array<int>|null $excludedColPos Excluded column positions (null = none)
     * @param int             $maxDepth       Maximum nesting depth (0 = unlimited)
     * @param int             $excludeUid     UID of content element to exclude (usually the TOC element itself)
     * @param bool            $useHeaderLink  Use header_link field if available (default: false)
     */
    public function __construct(
        public string $mode = 'visibleHeaders',
        public ?array $allowedColPos = null,
        public ?array $excludedColPos = null,
        public int $maxDepth = 0,
        public int $excludeUid = 0,
        public bool $useHeaderLink = false,
    ) {
    }

    /**
     * Create configuration from array (useful for backward compatibility).
     *
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        // Create instance to access TypeCastingTrait methods
        $helper = new self();

        /** @var array<int>|null $allowedColPos */
        $allowedColPos = isset($config['allowedColPos']) && is_array($config['allowedColPos']) ? $config['allowedColPos'] : null;

        /** @var array<int>|null $excludedColPos */
        $excludedColPos = isset($config['excludedColPos']) && is_array($config['excludedColPos']) ? $config['excludedColPos'] : null;

        return new self(
            mode: $helper->asString($config['mode'] ?? 'visibleHeaders'),
            allowedColPos: $allowedColPos,
            excludedColPos: $excludedColPos,
            maxDepth: $helper->asInt($config['maxDepth'] ?? 0),
            excludeUid: $helper->asInt($config['excludeUid'] ?? 0),
            useHeaderLink: (bool) ($config['useHeaderLink'] ?? false),
        );
    }
}
