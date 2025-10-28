<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Tests\Unit\Domain\Model;

use Ndrstmr\DpT3Toc\Domain\Model\TocItem;
use PHPUnit\Framework\TestCase;

final class TocItemTest extends TestCase
{
    public function testGetEffectiveColPosForTopLevelElement(): void
    {
        $item = new TocItem(
            data: ['uid' => 1, 'colPos' => 5, 'sorting' => 256],
            title: 'Test',
            anchor: '#c1',
            level: 2
        );

        $this->assertEquals(5, $item->getEffectiveColPos());
    }

    public function testGetEffectiveColPosForContainerChild(): void
    {
        $item = new TocItem(
            data: ['uid' => 1, 'colPos' => 200, 'sorting' => 100],
            title: 'Test',
            anchor: '#c1',
            level: 3,
            path: [
                ['uid' => 10, 'ctype' => 'container_2col', 'colPos' => 0, 'sorting' => 256],
            ]
        );

        // Container child (colPos 200) should use parent's colPos (0)
        $this->assertEquals(0, $item->getEffectiveColPos());
    }

    public function testGetEffectiveSortingForTopLevelElement(): void
    {
        $item = new TocItem(
            data: ['uid' => 1, 'colPos' => 0, 'sorting' => 512],
            title: 'Test',
            anchor: '#c1',
            level: 2
        );

        $this->assertEquals(512, $item->getEffectiveSorting());
    }

    public function testGetEffectiveSortingForContainerChild(): void
    {
        $item = new TocItem(
            data: ['uid' => 1, 'colPos' => 200, 'sorting' => 100],
            title: 'Test',
            anchor: '#c1',
            level: 3,
            path: [
                ['uid' => 10, 'ctype' => 'container_2col', 'colPos' => 0, 'sorting' => 256],
            ]
        );

        // Container child should use parent's sorting
        $this->assertEquals(256, $item->getEffectiveSorting());
    }

    public function testToArray(): void
    {
        $data = ['uid' => 1, 'header' => 'My Header', 'colPos' => 0];
        $item = new TocItem(
            data: $data,
            title: 'My Header',
            anchor: '#c1',
            level: 2,
            path: []
        );

        $array = $item->toArray();

        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('anchor', $array);
        $this->assertArrayHasKey('level', $array);
        $this->assertArrayHasKey('path', $array);

        $this->assertEquals($data, $array['data']);
        $this->assertEquals('My Header', $array['title']);
        $this->assertEquals('#c1', $array['anchor']);
        $this->assertEquals(2, $array['level']);
    }

    public function testImmutability(): void
    {
        $item = new TocItem(
            data: ['uid' => 1],
            title: 'Test',
            anchor: '#c1',
            level: 2
        );

        // readonly properties cannot be modified
        $this->expectException(\Error::class);
        $item->title = 'Changed'; // @phpstan-ignore-line
    }
}
