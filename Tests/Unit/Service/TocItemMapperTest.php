<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Tests\Unit\Service;

use Ndrstmr\DpT3Toc\Service\TocItemMapper;
use PHPUnit\Framework\TestCase;

final class TocItemMapperTest extends TestCase
{
    private TocItemMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new TocItemMapper();
    }

    /**
     * Test basic TocItem creation from database row.
     */
    public function testMapFromRowCreatesBasicTocItem(): void
    {
        $row = [
            'uid' => 123,
            'header' => 'Test Header',
            'colPos' => 0,
            'sorting' => 256,
            'CType' => 'text',
        ];

        $item = $this->mapper->mapFromRow($row, 2, []);

        static::assertEquals('Test Header', $item->title);
        static::assertEquals('#c123', $item->anchor);
        static::assertEquals(2, $item->level);
        static::assertEmpty($item->path);
    }

    /**
     * Test that default anchor format is #c{uid}.
     */
    public function testMapFromRowUsesDefaultAnchorFormat(): void
    {
        $row = [
            'uid' => 456,
            'header' => 'Header without custom anchor',
            'header_link' => '',
        ];

        // Default: useHeaderLink = false
        $item = $this->mapper->mapFromRow($row, 2, [], false);

        static::assertEquals('#c456', $item->anchor);
    }

    /**
     * Test custom anchor from header_link field when enabled.
     */
    public function testMapFromRowUsesHeaderLinkWhenEnabled(): void
    {
        $row = [
            'uid' => 789,
            'header' => 'Header with custom anchor',
            'header_link' => 'custom-anchor',
        ];

        $item = $this->mapper->mapFromRow($row, 2, [], true);

        static::assertEquals('#custom-anchor', $item->anchor);
    }

    /**
     * Test that header_link falls back to #c{uid} when empty.
     */
    public function testMapFromRowFallsBackWhenHeaderLinkEmpty(): void
    {
        $row = [
            'uid' => 101,
            'header' => 'Header',
            'header_link' => '   ',
        ];

        $item = $this->mapper->mapFromRow($row, 2, [], true);

        static::assertEquals('#c101', $item->anchor);
    }

    /**
     * Test XSS prevention in header_link anchor.
     */
    public function testMapFromRowSanitizesInvalidAnchors(): void
    {
        $testCases = [
            ['uid' => 1, 'header_link' => '"><script>alert("xss")</script>', 'expected' => '#c1'],
            ['uid' => 2, 'header_link' => '" onload="alert(1)"', 'expected' => '#c2'],
            ['uid' => 3, 'header_link' => '<img src=x onerror=alert(1)>', 'expected' => '#c3'],
            ['uid' => 4, 'header_link' => 'anchor!@#$%^&*()', 'expected' => '#c4'],
            ['uid' => 5, 'header_link' => 'anchor with space', 'expected' => '#c5'],
            ['uid' => 6, 'header_link' => 'anchor.section', 'expected' => '#c6'],
            ['uid' => 7, 'header_link' => 'anchör-ümläüt', 'expected' => '#c7'],
        ];

        foreach ($testCases as $testCase) {
            $row = [
                'uid' => $testCase['uid'],
                'header' => 'Test',
                'header_link' => $testCase['header_link'],
            ];

            $item = $this->mapper->mapFromRow($row, 2, [], true);

            static::assertEquals(
                $testCase['expected'],
                $item->anchor,
                "Failed for header_link: {$testCase['header_link']}"
            );
        }
    }

    /**
     * Test valid anchor formats that should pass validation.
     */
    public function testMapFromRowAcceptsValidAnchors(): void
    {
        $validCases = [
            ['header_link' => 'valid_anchor-123', 'expected' => '#valid_anchor-123'],
            ['header_link' => '12345', 'expected' => '#12345'],
            ['header_link' => 'abcXYZ', 'expected' => '#abcXYZ'],
            ['header_link' => '_-_-', 'expected' => '#_-_-'],
        ];

        foreach ($validCases as $index => $testCase) {
            $row = [
                'uid' => 100 + $index,
                'header' => 'Test',
                'header_link' => $testCase['header_link'],
            ];

            $item = $this->mapper->mapFromRow($row, 2, [], true);

            static::assertEquals(
                $testCase['expected'],
                $item->anchor,
                "Failed for valid anchor: {$testCase['header_link']}"
            );
        }
    }

    /**
     * Test TocItem creation with path (nested containers).
     */
    public function testMapFromRowWithPath(): void
    {
        $path = [
            ['uid' => 10, 'ctype' => 'container_2col', 'colPos' => 0, 'sorting' => 100],
            ['uid' => 20, 'ctype' => 'container_nested', 'colPos' => 200, 'sorting' => 200],
        ];

        $row = [
            'uid' => 30,
            'header' => 'Nested Item',
        ];

        $item = $this->mapper->mapFromRow($row, 4, $path);

        static::assertCount(2, $item->path);
        static::assertEquals(10, $item->path[0]['uid']);
        static::assertEquals(20, $item->path[1]['uid']);
    }

    /**
     * Test TocItem creation with different levels.
     */
    public function testMapFromRowWithDifferentLevels(): void
    {
        $row = ['uid' => 1, 'header' => 'Test'];

        $level2 = $this->mapper->mapFromRow($row, 2, []);
        $level3 = $this->mapper->mapFromRow($row, 3, []);
        $level5 = $this->mapper->mapFromRow($row, 5, []);

        static::assertEquals(2, $level2->level);
        static::assertEquals(3, $level3->level);
        static::assertEquals(5, $level5->level);
    }

    /**
     * Test that data array is preserved in TocItem.
     */
    public function testMapFromRowPreservesDataArray(): void
    {
        $row = [
            'uid' => 999,
            'header' => 'Test',
            'colPos' => 1,
            'sorting' => 512,
            'CType' => 'textpic',
            'custom_field' => 'custom_value',
        ];

        $item = $this->mapper->mapFromRow($row, 2, []);

        static::assertSame($row, $item->data);
        static::assertEquals('custom_value', $item->data['custom_field']);
    }

    /**
     * Test header extraction with trimming.
     */
    public function testMapFromRowTrimsHeader(): void
    {
        $row = [
            'uid' => 1,
            'header' => '  Whitespace Header  ',
        ];

        $item = $this->mapper->mapFromRow($row, 2, []);

        static::assertEquals('Whitespace Header', $item->title);
    }

    /**
     * Test empty header handling.
     */
    public function testMapFromRowHandlesEmptyHeader(): void
    {
        $row = [
            'uid' => 1,
            'header' => '',
        ];

        $item = $this->mapper->mapFromRow($row, 2, []);

        static::assertEquals('', $item->title);
    }

    /**
     * Test missing header field.
     */
    public function testMapFromRowHandlesMissingHeader(): void
    {
        $row = [
            'uid' => 1,
        ];

        $item = $this->mapper->mapFromRow($row, 2, []);

        static::assertEquals('', $item->title);
    }
}
