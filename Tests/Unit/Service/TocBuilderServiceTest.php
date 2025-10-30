<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Tests\Unit\Service;

use Ndrstmr\DpT3Toc\Domain\Repository\ContentElementRepositoryInterface;
use Ndrstmr\DpT3Toc\Service\TcaContainerCheckServiceInterface;
use Ndrstmr\DpT3Toc\Service\TocBuilderService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TocBuilderServiceTest extends TestCase
{
    private TocBuilderService $service;
    private MockObject&ContentElementRepositoryInterface $mockRepo;
    private MockObject&TcaContainerCheckServiceInterface $mockContainerCheck;

    protected function setUp(): void
    {
        $this->mockRepo = $this->createMock(ContentElementRepositoryInterface::class);
        $this->mockContainerCheck = $this->createMock(TcaContainerCheckServiceInterface::class);

        $this->service = new TocBuilderService($this->mockRepo, $this->mockContainerCheck);
    }

    public function testBuildForPageWithSectionIndexMode(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'Header 1', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Header 2', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'Header 3', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly');

        static::assertCount(2, $toc);
        static::assertEquals('Header 1', $toc[0]->title);
        static::assertEquals('#c1', $toc[0]->anchor);
        static::assertEquals('Header 3', $toc[1]->title);
    }

    public function testBuildForPageWithContainer(): void
    {
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

        $this->mockContainerCheck->method('isContainer')
            ->willReturnCallback(static fn (string $ctype): bool => 'container_2col' === $ctype);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly');

        static::assertCount(4, $toc);
        static::assertEquals('Before Container', $toc[0]->title);
        static::assertEquals('Child 1', $toc[1]->title);
        static::assertEquals('Child 2', $toc[2]->title);
        static::assertEquals('After Container', $toc[3]->title);

        // Verify container children have path
        static::assertNotEmpty($toc[1]->path);
        static::assertEquals(20, $toc[1]->path[0]['uid']);
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

        static::assertEquals('ColPos 0', $sorted[0]->title);
        static::assertEquals('ColPos 1', $sorted[1]->title);
        static::assertEquals('ColPos 2', $sorted[2]->title);
    }

    public function testEmptyResultWhenNoElements(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([]);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly');

        static::assertCount(0, $toc);
    }

    public function testEmptyResultWhenNoMatchingMode(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'Header', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly');

        static::assertCount(0, $toc);
    }

    public function testBuildForPageWithAllowedColPos(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'ColPos 0', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'ColPos 1', 'colPos' => 1, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'ColPos 2', 'colPos' => 2, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        // Only allow colPos 0 and 2
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', [0, 2]);

        static::assertCount(2, $toc);
        static::assertEquals('ColPos 0', $toc[0]->title);
        static::assertEquals('ColPos 2', $toc[1]->title);
    }

    public function testBuildForPageWithExcludedColPos(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'ColPos 0', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'ColPos 1', 'colPos' => 1, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'ColPos 2', 'colPos' => 2, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        // Exclude colPos 1
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', null, [1]);

        static::assertCount(2, $toc);
        static::assertEquals('ColPos 0', $toc[0]->title);
        static::assertEquals('ColPos 2', $toc[1]->title);
    }

    public function testBuildForPageWithBothAllowedAndExcluded(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'ColPos 0', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'ColPos 1', 'colPos' => 1, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'ColPos 2', 'colPos' => 2, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        // Allow 0,1,2 but exclude 1 (blacklist takes precedence)
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', [0, 1, 2], [1]);

        static::assertCount(2, $toc);
        static::assertEquals('ColPos 0', $toc[0]->title);
        static::assertEquals('ColPos 2', $toc[1]->title);
    }

    public function testBuildForPageExcludesCurrentUid(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'Header 1', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'TOC Element', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'list', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'Header 3', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        // Exclude UID 2 (the TOC element itself)
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', null, null, 0, 2);

        static::assertCount(2, $toc);
        static::assertEquals('Header 1', $toc[0]->title);
        static::assertEquals('Header 3', $toc[1]->title);
    }

    public function testBuildForPageWithMaxDepthLimitsNesting(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 10, 'header' => 'Top Level', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 20, 'header' => '', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'container_2col', 'header_layout' => 0],
        ]);

        $this->mockRepo->method('findContainerChildren')->willReturnCallback(function ($parentUid) {
            if (20 === $parentUid) {
                return [
                    ['uid' => 21, 'header' => 'Level 2', 'colPos' => 200, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'container_nested', 'header_layout' => 0],
                ];
            }
            if (21 === $parentUid) {
                return [
                    ['uid' => 22, 'header' => 'Level 3 (should be excluded)', 'colPos' => 201, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'text', 'header_layout' => 0],
                ];
            }

            return [];
        });

        $this->mockContainerCheck->method('isContainer')
            ->willReturnCallback(static fn (string $ctype): bool => in_array($ctype, ['container_2col', 'container_nested'], true));

        // maxDepth = 2 means we stop descending at level 2
        // Since container_2col is at level 2, it won't descend into children
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', null, null, 2);

        // We should only get Top Level (level 2), container at level 2 stops recursion
        static::assertCount(1, $toc);
        static::assertEquals('Top Level', $toc[0]->title);
    }

    public function testBuildForPageWithMaxDepthAllowsOneLevelDeeper(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 10, 'header' => 'Top Level', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 20, 'header' => '', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'container_2col', 'header_layout' => 0],
        ]);

        $this->mockRepo->method('findContainerChildren')->willReturnCallback(function ($parentUid) {
            if (20 === $parentUid) {
                return [
                    ['uid' => 21, 'header' => 'Level 3 Child', 'colPos' => 200, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'container_nested', 'header_layout' => 0],
                ];
            }
            if (21 === $parentUid) {
                return [
                    ['uid' => 22, 'header' => 'Level 4 (should be excluded)', 'colPos' => 201, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'text', 'header_layout' => 0],
                ];
            }

            return [];
        });

        $this->mockContainerCheck->method('isContainer')
            ->willReturnCallback(static fn (string $ctype): bool => in_array($ctype, ['container_2col', 'container_nested'], true));

        // maxDepth = 3 means we allow up to level 3, stop recursion at level 3
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', null, null, 3);

        // We get Top Level (level 2) and Level 3 Child (level 3), but NOT Level 4
        static::assertCount(2, $toc);
        static::assertEquals('Top Level', $toc[0]->title);
        static::assertEquals('Level 3 Child', $toc[1]->title);
    }

    public function testBuildForPageWithMaxDepthZeroIsUnlimited(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 20, 'header' => '', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'container_2col', 'header_layout' => 0],
        ]);

        $this->mockRepo->method('findContainerChildren')->willReturnCallback(function ($parentUid) {
            if (20 === $parentUid) {
                return [
                    ['uid' => 21, 'header' => 'Level 2', 'colPos' => 200, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'container_nested', 'header_layout' => 0],
                ];
            }
            if (21 === $parentUid) {
                return [
                    ['uid' => 22, 'header' => 'Level 3', 'colPos' => 201, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'text', 'header_layout' => 0],
                ];
            }

            return [];
        });

        $this->mockContainerCheck->method('isContainer')
            ->willReturnCallback(static fn (string $ctype): bool => in_array($ctype, ['container_2col', 'container_nested'], true));

        // maxDepth = 0 means unlimited depth
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', null, null, 0);

        static::assertCount(2, $toc);
        static::assertEquals('Level 2', $toc[0]->title);
        static::assertEquals('Level 3', $toc[1]->title);
    }

    public function testBuildForPageWithVisibleHeadersMode(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'Visible', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Hidden', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 100],
            ['uid' => 3, 'header' => '', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        $toc = $this->service->buildForPage(1, 'visibleHeaders');

        static::assertCount(1, $toc);
        static::assertEquals('Visible', $toc[0]->title);
    }

    public function testBuildForPageWithAllMode(): void
    {
        $this->mockRepo->method('findByPage')->willReturn([
            ['uid' => 1, 'header' => 'Header 1', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Header 2', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 100],
            ['uid' => 3, 'header' => '', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findContainerChildren')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        $toc = $this->service->buildForPage(1, 'all');

        // 'all' mode includes even hidden headers (header_layout = 100), but not empty headers
        static::assertCount(2, $toc);
        static::assertEquals('Header 1', $toc[0]->title);
        static::assertEquals('Header 2', $toc[1]->title);
    }
}
