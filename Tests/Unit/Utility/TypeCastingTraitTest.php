<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Tests\Unit\Utility;

use Ndrstmr\DpT3Toc\Utility\TypeCastingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TypeCastingTraitTest extends TestCase
{
    private TypeCastingTraitTestClass $instance;

    protected function setUp(): void
    {
        $this->instance = new TypeCastingTraitTestClass();
    }

    #[DataProvider('asIntDataProvider')]
    public function testAsInt(mixed $input, int $expected): void
    {
        $result = $this->instance->testAsInt($input);

        static::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: mixed, 1: int}>
     */
    public static function asIntDataProvider(): array
    {
        return [
            'integer' => [42, 42],
            'negative integer' => [-10, -10],
            'zero' => [0, 0],
            'numeric string' => ['123', 123],
            'negative numeric string' => ['-456', -456],
            'float' => [3.14, 3],
            'float string' => ['3.14', 3],
            'zero string' => ['0', 0],
            'empty string' => ['', 0],
            'non-numeric string' => ['abc', 0],
            'null' => [null, 0],
            'true' => [true, 0],
            'false' => [false, 0],
            'array' => [[], 0],
            'object' => [new \stdClass(), 0],
        ];
    }

    #[DataProvider('asStringDataProvider')]
    public function testAsString(mixed $input, string $expected): void
    {
        $result = $this->instance->testAsString($input);

        static::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function asStringDataProvider(): array
    {
        return [
            'string' => ['hello', 'hello'],
            'empty string' => ['', ''],
            'integer' => [42, '42'],
            'zero' => [0, '0'],
            'negative integer' => [-10, '-10'],
            'float' => [3.14, '3.14'],
            'true' => [true, '1'],
            'false' => [false, ''],
            'null' => [null, ''],
            'array' => [[], ''],
            'object' => [new \stdClass(), ''],
        ];
    }

    #[DataProvider('asFloatDataProvider')]
    public function testAsFloat(mixed $input, float $expected): void
    {
        $result = $this->instance->testAsFloat($input);

        static::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: mixed, 1: float}>
     */
    public static function asFloatDataProvider(): array
    {
        return [
            'float' => [3.14, 3.14],
            'integer' => [42, 42.0],
            'negative float' => [-2.5, -2.5],
            'zero' => [0, 0.0],
            'numeric string' => ['123.45', 123.45],
            'integer string' => ['42', 42.0],
            'zero string' => ['0', 0.0],
            'empty string' => ['', 0.0],
            'non-numeric string' => ['abc', 0.0],
            'null' => [null, 0.0],
            'true' => [true, 0.0],
            'false' => [false, 0.0],
            'array' => [[], 0.0],
            'object' => [new \stdClass(), 0.0],
        ];
    }

    #[DataProvider('asBoolDataProvider')]
    public function testAsBool(mixed $input, bool $expected): void
    {
        $result = $this->instance->testAsBool($input);

        static::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: mixed, 1: bool}>
     */
    public static function asBoolDataProvider(): array
    {
        return [
            'true' => [true, true],
            'false' => [false, false],
            'integer 1' => [1, true],
            'integer 0' => [0, false],
            'string true' => ['true', true],
            'string false' => ['false', false],
            'string 1' => ['1', true],
            'string 0' => ['0', false],
            'string on' => ['on', true],
            'string off' => ['off', false],
            'string yes' => ['yes', true],
            'string no' => ['no', false],
            'empty string' => ['', false],
            'non-empty string' => ['abc', false],
            'null' => [null, false],
            'array empty' => [[], false],
            'array non-empty' => [[1], false],
            'object' => [new \stdClass(), false],
        ];
    }

    /**
     * @param array<array-key, mixed> $expected
     */
    #[DataProvider('asArrayDataProvider')]
    public function testAsArray(mixed $input, array $expected): void
    {
        $result = $this->instance->testAsArray($input);

        static::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: mixed, 1: array<array-key, mixed>}>
     */
    public static function asArrayDataProvider(): array
    {
        return [
            'empty array' => [[], []],
            'indexed array' => [[1, 2, 3], [1, 2, 3]],
            'associative array' => [['a' => 1, 'b' => 2], ['a' => 1, 'b' => 2]],
            'nested array' => [['x' => ['y' => 'z']], ['x' => ['y' => 'z']]],
            'string' => ['hello', []],
            'integer' => [42, []],
            'float' => [3.14, []],
            'true' => [true, []],
            'false' => [false, []],
            'null' => [null, []],
            'object' => [new \stdClass(), []],
        ];
    }
}

/**
 * Test class to expose TypeCastingTrait methods.
 *
 * Since trait methods are private, we need a concrete class
 * that exposes them as public for testing purposes.
 */
final class TypeCastingTraitTestClass
{
    use TypeCastingTrait;

    public function testAsInt(mixed $value): int
    {
        return $this->asInt($value);
    }

    public function testAsString(mixed $value): string
    {
        return $this->asString($value);
    }

    public function testAsFloat(mixed $value): float
    {
        return $this->asFloat($value);
    }

    public function testAsBool(mixed $value): bool
    {
        return $this->asBool($value);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function testAsArray(mixed $value): array
    {
        return $this->asArray($value);
    }
}
