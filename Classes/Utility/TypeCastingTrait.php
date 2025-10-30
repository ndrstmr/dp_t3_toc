<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Utility;

/**
 * A trait for safe, PHPStan Max-Level-compliant type casting.
 *
 * Provides methods to safely cast 'mixed' values (e.g., from database rows)
 * to scalar types without triggering fatal errors on arrays or objects.
 */
trait TypeCastingTrait
{
    /**
     * Safely cast a mixed value to int, satisfying PHPStan Max Level.
     *
     * @param mixed $value The value to cast
     */
    private function asInt(mixed $value): int
    {
        // is_numeric() correctly handles strings, floats, and ints,
        // but returns false for arrays or objects, preventing fatal errors.
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * Safely cast a mixed value to string, satisfying PHPStan Max Level.
     *
     * @param mixed $value The value to cast
     */
    private function asString(mixed $value): string
    {
        // is_scalar() checks for string, int, float, bool.
        // We also allow null, which casts to an empty string.
        if (is_scalar($value) || null === $value) {
            return (string) $value;
        }

        return '';
    }

    /**
     * Safely cast a mixed value to float, satisfying PHPStan Max Level.
     *
     * @param mixed $value The value to cast
     */
    private function asFloat(mixed $value): float
    {
        // Same logic as asInt()
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * Safely cast a mixed value to bool, satisfying PHPStan Max Level.
     *
     * This uses FILTER_VALIDATE_BOOLEAN to correctly interpret
     * 'false', '0', 'off', 'no', '', and null as false.
     *
     * @param mixed $value The value to cast
     */
    private function asBool(mixed $value): bool
    {
        // filter_var is type-safe and correctly handles all common
        // boolean representations (incl. strings like 'on', 'false', etc.)
        // It returns false for arrays or objects.
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Safely ensure a mixed value is an array (type guard).
     *
     * @param mixed $value The value to check
     *
     * @return array<array-key, mixed>
     */
    private function asArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
