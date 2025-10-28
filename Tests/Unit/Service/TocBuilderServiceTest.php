<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Tests\Unit\Service;

use Ndrstmr\DpT3Toc\Domain\Repository\ContentElementRepositoryInterface;
use Ndrstmr\DpT3Toc\Service\TocBuilderService;
use PHPUnit\Framework\TestCase;

final class TocBuilderServiceTest extends TestCase
{
    private TocBuilderService $service;
    private ContentElementRepositoryInterface $mockRepo;

    protected function setUp(): void
    {
        $this->mockRepo = $this->createMock(ContentElementRepositoryInterface::class);
        $this->service = new TocBuilderService($this->mockRepo);
    }

    public function testBuildForPageWithSectionIndexMode(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'Header 1', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Header 2', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'Header 3', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly');

        $this->assertCount(2, $toc);
        $this->assertEquals('Header 1', $toc[0]->title);
        $this->assertEquals('#c1', $toc[0]->anchor);
        $this->assertEquals('Header 3', $toc[1]->title);
    }

    public function testBuildForPageWithVisibleHeadersMode(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'Visible', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Hidden', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 100],
            ['uid' => 3, 'header' => 'Visible 2', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 1],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);

        $toc = $this->service->buildForPage(1, 'visibleHeaders');

        $this->assertCount(2, $toc);
        $this->assertEquals('Visible', $toc[0]->title);
        $this->assertEquals('Visible 2', $toc[1]->title);
    }

    public function testBuildForPageWithExcludeColPos(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'Main Content', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Sidebar', 'colPos' => 5, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'Footer', 'colPos' => 88, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', null, [5, 88]);

        $this->assertCount(1, $toc);
        $this->assertEquals('Main Content', $toc[0]->title);
        $this->assertEquals(0, $toc[0]->data['colPos']);
    }

    public function testBuildForPageWithIncludeColPos(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'Col 0', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Col 1', 'colPos' => 1, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'Col 5', 'colPos' => 5, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', [0, 1]);

        $this->assertCount(2, $toc);
        $this->assertEquals('Col 0', $toc[0]->title);
        $this->assertEquals('Col 1', $toc[1]->title);
    }

    public function testBuildForPageWithContainer(): void
    {
        // Mock: Container element with children
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 10, 'header' => 'Before Container', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 20, 'header' => '', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'container_2col', 'header_layout' => 0],
            ['uid' => 30, 'header' => 'After Container', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);

        $this->mockRepo->method('findContainerChildren')->willReturnCallback(function ($parentUid) {
            if (20 === $parentUid) {
                return [
                    ['uid' => 21, 'header' => 'Child 1', 'colPos' => 200, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'text', 'header_layout' => 0, 'tx_container_parent' => 20],
                    ['uid' => 22, 'header' => 'Child 2', 'colPos' => 201, 'sectionIndex' => 1, 'sorting' => 200, 'CType' => 'text', 'header_layout' => 0, 'tx_container_parent' => 20],
                ];
            }

            return [];
        });

        // Mock TCA for container detection
        $GLOBALS['TCA']['tt_content']['containerConfiguration']['container_2col'] = [];

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly');

        $this->assertCount(4, $toc);
        $this->assertEquals('Before Container', $toc[0]->title);
        $this->assertEquals('Child 1', $toc[1]->title);
        $this->assertEquals('Child 2', $toc[2]->title);
        $this->assertEquals('After Container', $toc[3]->title);

        // Verify container children have path
        $this->assertNotEmpty($toc[1]->path);
        $this->assertEquals(20, $toc[1]->path[0]['uid']);
    }

    public function testSortItemsByColPos(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 3, 'header' => 'ColPos 2', 'colPos' => 2, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 1, 'header' => 'ColPos 0', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'ColPos 1', 'colPos' => 1, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly');
        $sorted = $this->service->sortItems($toc);

        $this->assertEquals('ColPos 0', $sorted[0]->title);
        $this->assertEquals('ColPos 1', $sorted[1]->title);
        $this->assertEquals('ColPos 2', $sorted[2]->title);
    }

    public function testEmptyResultWhenNoElements(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([]);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly');

        $this->assertCount(0, $toc);
    }

    public function testEmptyResultWhenNoMatchingMode(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'Header', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly');

        $this->assertCount(0, $toc);
    }
}
