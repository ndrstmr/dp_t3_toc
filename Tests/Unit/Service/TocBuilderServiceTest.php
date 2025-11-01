<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Tests\Unit\Service;

use Ndrstmr\DpT3Toc\Domain\Model\TocConfiguration;
use Ndrstmr\DpT3Toc\Domain\Repository\ContentElementRepositoryInterface;
use Ndrstmr\DpT3Toc\Event\AfterTocItemsBuiltEvent;
use Ndrstmr\DpT3Toc\Event\BeforeTocItemsBuiltEvent;
use Ndrstmr\DpT3Toc\Event\TocItemFilterEvent;
use Ndrstmr\DpT3Toc\Service\TcaContainerCheckServiceInterface;
use Ndrstmr\DpT3Toc\Service\TocBuilderService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

final class TocBuilderServiceTest extends TestCase
{
    private TocBuilderService $service;
    private MockObject&ContentElementRepositoryInterface $mockRepo;
    private MockObject&TcaContainerCheckServiceInterface $mockContainerCheck;
    private MockObject&LoggerInterface $mockLogger;
    private MockObject&EventDispatcherInterface $mockEventDispatcher;

    protected function setUp(): void
    {
        $this->mockRepo = $this->createMock(ContentElementRepositoryInterface::class);
        $this->mockContainerCheck = $this->createMock(TcaContainerCheckServiceInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->mockEventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->service = new TocBuilderService(
            $this->mockRepo,
            $this->mockContainerCheck,
            $this->mockLogger,
            $this->mockEventDispatcher
        );
    }

    public function testBuildForPageWithSectionIndexMode(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Header 1', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Header 2', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'Header 3', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly');

        static::assertCount(2, $toc);
        static::assertEquals('Header 1', $toc[0]->title);
        static::assertEquals('#c1', $toc[0]->anchor);
        static::assertEquals('Header 3', $toc[1]->title);
    }

    public function testBuildForPageWithContainer(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 10, 'header' => 'Before Container', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 20, 'header' => '', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'container_2col', 'header_layout' => 0],
            ['uid' => 30, 'header' => 'After Container', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);

        // Eager loading: All container children in one flat list
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([
            ['uid' => 21, 'header' => 'Child 1', 'colPos' => 200, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'text', 'header_layout' => 0, 'tx_container_parent' => 20],
            ['uid' => 22, 'header' => 'Child 2', 'colPos' => 201, 'sectionIndex' => 1, 'sorting' => 200, 'CType' => 'text', 'header_layout' => 0, 'tx_container_parent' => 20],
        ]);

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
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 3, 'header' => 'ColPos 2', 'colPos' => 2, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 1, 'header' => 'ColPos 0', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'ColPos 1', 'colPos' => 1, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly');
        $sorted = $this->service->sortItems($toc);

        static::assertEquals('ColPos 0', $sorted[0]->title);
        static::assertEquals('ColPos 1', $sorted[1]->title);
        static::assertEquals('ColPos 2', $sorted[2]->title);
    }

    public function testEmptyResultWhenNoElements(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([]);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly');

        static::assertCount(0, $toc);
    }

    public function testEmptyResultWhenNoMatchingMode(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Header', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);

        $toc = $this->service->buildForPage(1, 'sectionIndexOnly');

        static::assertCount(0, $toc);
    }

    public function testBuildForPageWithAllowedColPos(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'ColPos 0', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'ColPos 1', 'colPos' => 1, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'ColPos 2', 'colPos' => 2, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        // Only allow colPos 0 and 2
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', [0, 2]);

        static::assertCount(2, $toc);
        static::assertEquals('ColPos 0', $toc[0]->title);
        static::assertEquals('ColPos 2', $toc[1]->title);
    }

    public function testBuildForPageWithExcludedColPos(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'ColPos 0', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'ColPos 1', 'colPos' => 1, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'ColPos 2', 'colPos' => 2, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        // Exclude colPos 1
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', null, [1]);

        static::assertCount(2, $toc);
        static::assertEquals('ColPos 0', $toc[0]->title);
        static::assertEquals('ColPos 2', $toc[1]->title);
    }

    /**
     * Test that container children inherit parent's colPos visibility.
     *
     * Scenario: Container in colPos=0, children have internal colPos=200,201.
     * Filter: includeColPos=[0]
     * Expected: Container + all children are included (children inherit parent visibility).
     *
     * This test verifies the fix for the critical bug where container children
     * were incorrectly filtered out by their internal colPos values.
     */
    public function testContainerChildrenInheritParentColPosVisibility(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 100, 'header' => 'Container in ColPos 0', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'container_2col', 'header_layout' => 0],
            ['uid' => 200, 'header' => 'Element in ColPos 1 (excluded)', 'colPos' => 1, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
        ]);

        // Container children with internal colPos values (200, 201)
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([
            ['uid' => 101, 'header' => 'Child 1 (internal colPos=200)', 'colPos' => 200, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'text', 'header_layout' => 0, 'tx_container_parent' => 100],
            ['uid' => 102, 'header' => 'Child 2 (internal colPos=201)', 'colPos' => 201, 'sectionIndex' => 1, 'sorting' => 200, 'CType' => 'text', 'header_layout' => 0, 'tx_container_parent' => 100],
        ]);

        $this->mockContainerCheck->method('isContainer')
            ->willReturnCallback(static fn (string $ctype): bool => 'container_2col' === $ctype);

        // Filter: Only allow colPos=0
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', [0]);

        // Expected: Container header + both children (inherit parent's visibility)
        // NOT expected: Element in ColPos 1
        static::assertCount(3, $toc);
        static::assertEquals('Container in ColPos 0', $toc[0]->title);
        static::assertEquals('Child 1 (internal colPos=200)', $toc[1]->title);
        static::assertEquals('Child 2 (internal colPos=201)', $toc[2]->title);

        // Verify children have correct path to parent
        static::assertNotEmpty($toc[1]->path);
        static::assertEquals(100, $toc[1]->path[0]['uid']);
        static::assertEquals(0, $toc[1]->path[0]['colPos']); // Parent's colPos, not child's internal value
    }

    public function testBuildForPageWithBothAllowedAndExcluded(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'ColPos 0', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'ColPos 1', 'colPos' => 1, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'ColPos 2', 'colPos' => 2, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        // Allow 0,1,2 but exclude 1 (blacklist takes precedence)
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', [0, 1, 2], [1]);

        static::assertCount(2, $toc);
        static::assertEquals('ColPos 0', $toc[0]->title);
        static::assertEquals('ColPos 2', $toc[1]->title);
    }

    public function testBuildForPageExcludesCurrentUid(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Header 1', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'TOC Element', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'list', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'Header 3', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        // Exclude UID 2 (the TOC element itself)
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', null, null, 0, 2);

        static::assertCount(2, $toc);
        static::assertEquals('Header 1', $toc[0]->title);
        static::assertEquals('Header 3', $toc[1]->title);
    }

    public function testBuildForPageWithMaxDepthLimitsNesting(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 10, 'header' => 'Top Level', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 20, 'header' => '', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'container_2col', 'header_layout' => 0],
        ]);

        // Eager loading: All nested container children
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([
            ['uid' => 21, 'header' => 'Level 2', 'colPos' => 200, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'container_nested', 'header_layout' => 0, 'tx_container_parent' => 20],
            ['uid' => 22, 'header' => 'Level 3 (should be excluded)', 'colPos' => 201, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'text', 'header_layout' => 0, 'tx_container_parent' => 21],
        ]);

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
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 10, 'header' => 'Top Level', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 20, 'header' => '', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'container_2col', 'header_layout' => 0],
        ]);

        // Eager loading: All nested levels
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([
            ['uid' => 21, 'header' => 'Level 3 Child', 'colPos' => 200, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'container_nested', 'header_layout' => 0, 'tx_container_parent' => 20],
            ['uid' => 22, 'header' => 'Level 4 (should be excluded)', 'colPos' => 201, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'text', 'header_layout' => 0, 'tx_container_parent' => 21],
        ]);

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
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 20, 'header' => '', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'container_2col', 'header_layout' => 0],
        ]);

        // Eager loading: All nested levels
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([
            ['uid' => 21, 'header' => 'Level 2', 'colPos' => 200, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'container_nested', 'header_layout' => 0, 'tx_container_parent' => 20],
            ['uid' => 22, 'header' => 'Level 3', 'colPos' => 201, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'text', 'header_layout' => 0, 'tx_container_parent' => 21],
        ]);

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
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Visible', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Hidden', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 100],
            ['uid' => 3, 'header' => '', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        $toc = $this->service->buildForPage(1, 'visibleHeaders');

        static::assertCount(1, $toc);
        static::assertEquals('Visible', $toc[0]->title);
    }

    public function testBuildForPageWithAllMode(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Header 1', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Header 2', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 100],
            ['uid' => 3, 'header' => '', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        $toc = $this->service->buildForPage(1, 'all');

        // 'all' mode includes even hidden headers (header_layout = 100), but not empty headers
        static::assertCount(2, $toc);
        static::assertEquals('Header 1', $toc[0]->title);
        static::assertEquals('Header 2', $toc[1]->title);
    }

    /**
     * Test default anchor generation (useHeaderLink=false).
     *
     * Default behavior: Always use #c{uid} format, regardless of header_link field.
     */
    public function testBuildForPageWithDefaultAnchorGeneration(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Header 1', 'header_link' => 'custom-anchor', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Header 2', 'header_link' => '', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        // Default: useHeaderLink=false
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', null, null, 0, 0, false);

        static::assertCount(2, $toc);
        // Both should use #c{uid} format (default behavior)
        static::assertEquals('#c1', $toc[0]->anchor);
        static::assertEquals('#c2', $toc[1]->anchor);
    }

    /**
     * Test configurable anchor generation with header_link field (useHeaderLink=true).
     *
     * When enabled:
     * - Uses header_link field value if available
     * - Falls back to #c{uid} if header_link is empty
     */
    public function testBuildForPageWithHeaderLinkAnchorGeneration(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Header with Link', 'header_link' => 'custom-anchor', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Header without Link', 'header_link' => '', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'Header with Whitespace Link', 'header_link' => '  ', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        // Enable header_link usage
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', null, null, 0, 0, true);

        static::assertCount(3, $toc);

        // Element with header_link should use custom anchor
        static::assertEquals('#custom-anchor', $toc[0]->anchor);
        static::assertEquals('Header with Link', $toc[0]->title);

        // Element without header_link should fall back to #c{uid}
        static::assertEquals('#c2', $toc[1]->anchor);
        static::assertEquals('Header without Link', $toc[1]->title);

        // Element with whitespace-only header_link should fall back to #c{uid}
        static::assertEquals('#c3', $toc[2]->anchor);
        static::assertEquals('Header with Whitespace Link', $toc[2]->title);
    }

    /**
     * Test that header_link anchor works with nested containers.
     *
     * Container children should inherit the useHeaderLink setting.
     */
    public function testBuildForPageWithHeaderLinkInContainerChildren(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 20, 'header' => 'Container', 'header_link' => 'container-anchor', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'container_2col', 'header_layout' => 0],
        ]);

        // Container children with mixed header_link availability
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([
            ['uid' => 21, 'header' => 'Child with Link', 'header_link' => 'child-anchor', 'colPos' => 200, 'sectionIndex' => 1, 'sorting' => 100, 'CType' => 'text', 'header_layout' => 0, 'tx_container_parent' => 20],
            ['uid' => 22, 'header' => 'Child without Link', 'header_link' => '', 'colPos' => 201, 'sectionIndex' => 1, 'sorting' => 200, 'CType' => 'text', 'header_layout' => 0, 'tx_container_parent' => 20],
        ]);

        $this->mockContainerCheck->method('isContainer')
            ->willReturnCallback(static fn (string $ctype): bool => 'container_2col' === $ctype);

        // Enable header_link usage
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', null, null, 0, 0, true);

        static::assertCount(3, $toc);

        // Container uses custom anchor
        static::assertEquals('#container-anchor', $toc[0]->anchor);

        // Child with header_link uses custom anchor
        static::assertEquals('#child-anchor', $toc[1]->anchor);

        // Child without header_link falls back to #c{uid}
        static::assertEquals('#c22', $toc[2]->anchor);
    }

    /**
     * Test anchor sanitization against XSS attacks.
     *
     * Security: header_link field must be validated to prevent XSS.
     * Only alphanumeric, underscore, and hyphen are allowed.
     * Invalid characters should trigger fallback to #c{uid}.
     */
    public function testAnchorSanitizationPreventXSS(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            // XSS attempt: JavaScript injection
            ['uid' => 1, 'header' => 'XSS Test 1', 'header_link' => '"><script>alert("xss")</script>', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            // XSS attempt: Event handler
            ['uid' => 2, 'header' => 'XSS Test 2', 'header_link' => '" onload="alert(1)"', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            // XSS attempt: HTML injection
            ['uid' => 3, 'header' => 'XSS Test 3', 'header_link' => '<img src=x onerror=alert(1)>', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
            // Special characters (not XSS but invalid)
            ['uid' => 4, 'header' => 'Special Chars', 'header_link' => 'anchor!@#$%^&*()', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 1024, 'CType' => 'text', 'header_layout' => 0],
            // Valid anchor (control)
            ['uid' => 5, 'header' => 'Valid Anchor', 'header_link' => 'valid_anchor-123', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 1280, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        // Enable header_link usage
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', null, null, 0, 0, true);

        static::assertCount(5, $toc);

        // All XSS attempts should fall back to #c{uid}
        static::assertEquals('#c1', $toc[0]->anchor, 'JavaScript injection should be rejected');
        static::assertEquals('#c2', $toc[1]->anchor, 'Event handler should be rejected');
        static::assertEquals('#c3', $toc[2]->anchor, 'HTML injection should be rejected');
        static::assertEquals('#c4', $toc[3]->anchor, 'Special characters should be rejected');

        // Valid anchor should pass validation
        static::assertEquals('#valid_anchor-123', $toc[4]->anchor, 'Valid anchor should be accepted');
    }

    /**
     * Test edge cases for anchor sanitization.
     *
     * Tests boundary conditions and edge cases for the validation regex.
     */
    public function testAnchorSanitizationEdgeCases(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            // Empty after trim (already handled, but verify)
            ['uid' => 1, 'header' => 'Empty Link', 'header_link' => '   ', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            // Only numbers (valid)
            ['uid' => 2, 'header' => 'Numbers Only', 'header_link' => '12345', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            // Only letters (valid)
            ['uid' => 3, 'header' => 'Letters Only', 'header_link' => 'abcXYZ', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
            // Only underscores and hyphens (valid)
            ['uid' => 4, 'header' => 'Separators', 'header_link' => '_-_-', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 1024, 'CType' => 'text', 'header_layout' => 0],
            // Space in anchor (invalid)
            ['uid' => 5, 'header' => 'With Space', 'header_link' => 'anchor with space', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 1280, 'CType' => 'text', 'header_layout' => 0],
            // Dot/period (invalid - not in whitelist)
            ['uid' => 6, 'header' => 'With Dot', 'header_link' => 'anchor.section', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 1536, 'CType' => 'text', 'header_layout' => 0],
            // Unicode characters (invalid)
            ['uid' => 7, 'header' => 'Unicode', 'header_link' => 'anchör-ümläüt', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 1792, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        // Enable header_link usage
        $toc = $this->service->buildForPage(1, 'sectionIndexOnly', null, null, 0, 0, true);

        static::assertCount(7, $toc);

        // Empty/whitespace should fall back
        static::assertEquals('#c1', $toc[0]->anchor);

        // Valid formats should pass
        static::assertEquals('#12345', $toc[1]->anchor);
        static::assertEquals('#abcXYZ', $toc[2]->anchor);
        static::assertEquals('#_-_-', $toc[3]->anchor);

        // Invalid formats should fall back
        static::assertEquals('#c5', $toc[4]->anchor, 'Space should be rejected');
        static::assertEquals('#c6', $toc[5]->anchor, 'Dot should be rejected');
        static::assertEquals('#c7', $toc[6]->anchor, 'Unicode should be rejected');
    }

    /**
     * Test new configuration-based method buildForPageWithConfig().
     *
     * Verifies that TocConfiguration Value Object works correctly.
     */
    public function testBuildForPageWithConfig(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Header 1', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Header 2', 'colPos' => 1, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 3, 'header' => 'Header 3', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 768, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        $config = new TocConfiguration(
            mode: 'sectionIndexOnly',
            allowedColPos: [0],
            excludedColPos: null,
            maxDepth: 0,
            excludeUid: 0,
            useHeaderLink: false
        );

        $toc = $this->service->buildForPageWithConfig(1, $config);

        static::assertCount(2, $toc);
        static::assertEquals('Header 1', $toc[0]->title);
        static::assertEquals('Header 3', $toc[1]->title);
    }

    /**
     * Test configuration-based method buildForPagesWithConfig().
     *
     * Verifies multi-page TOC building with Value Object.
     */
    public function testBuildForPagesWithConfig(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Page 1 Header', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Page 2 Header', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        $config = new TocConfiguration(
            mode: 'sectionIndexOnly'
        );

        $toc = $this->service->buildForPagesWithConfig([1, 2], $config);

        static::assertCount(2, $toc);
        static::assertEquals('Page 1 Header', $toc[0]->title);
        static::assertEquals('Page 2 Header', $toc[1]->title);
    }

    /**
     * Test TocConfiguration::fromArray() factory method.
     *
     * Ensures backward compatibility when migrating from array configs.
     */
    public function testTocConfigurationFromArray(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Header', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        $config = TocConfiguration::fromArray([
            'mode' => 'sectionIndexOnly',
            'allowedColPos' => [0],
            'maxDepth' => 2,
            'excludeUid' => 99,
            'useHeaderLink' => true,
        ]);

        $toc = $this->service->buildForPageWithConfig(1, $config);

        static::assertCount(1, $toc);
        static::assertEquals('Header', $toc[0]->title);
    }

    /**
     * Test that BeforeTocItemsBuiltEvent is dispatched.
     *
     * Verifies PSR-14 event dispatching before TOC building starts.
     */
    public function testBeforeTocItemsBuiltEventIsDispatched(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);

        $config = new TocConfiguration(mode: 'sectionIndexOnly');

        // Expect 2 events: Before + After (no items, so no TocItemFilterEvent)
        $dispatchCount = 0;
        $this->mockEventDispatcher->expects(static::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$dispatchCount): object {
                ++$dispatchCount;
                if (1 === $dispatchCount) {
                    static::assertInstanceOf(BeforeTocItemsBuiltEvent::class, $event, 'First event should be BeforeTocItemsBuiltEvent');
                } elseif (2 === $dispatchCount) {
                    static::assertInstanceOf(AfterTocItemsBuiltEvent::class, $event, 'Second event should be AfterTocItemsBuiltEvent');
                }

                return $event;
            });

        $this->service->buildForPagesWithConfig([1], $config);
    }

    /**
     * Test that AfterTocItemsBuiltEvent is dispatched.
     *
     * Verifies PSR-14 event dispatching after TOC is built.
     */
    public function testAfterTocItemsBuiltEventIsDispatched(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Header', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        $config = new TocConfiguration(mode: 'sectionIndexOnly');

        // Expect 3 events: Before + TocItemFilter + After
        $dispatchCount = 0;
        $this->mockEventDispatcher->expects(static::exactly(3))
            ->method('dispatch')
            ->willReturnCallback(static function (object $event) use (&$dispatchCount): object {
                ++$dispatchCount;
                if (1 === $dispatchCount) {
                    static::assertInstanceOf(BeforeTocItemsBuiltEvent::class, $event, 'First event should be BeforeTocItemsBuiltEvent');
                } elseif (2 === $dispatchCount) {
                    static::assertInstanceOf(TocItemFilterEvent::class, $event, 'Second event should be TocItemFilterEvent');
                } elseif (3 === $dispatchCount) {
                    static::assertInstanceOf(AfterTocItemsBuiltEvent::class, $event, 'Third event should be AfterTocItemsBuiltEvent');
                }

                return $event;
            });

        $this->service->buildForPagesWithConfig([1], $config);
    }

    /**
     * Test that TocItemFilterEvent is dispatched for each TOC item.
     *
     * Verifies PSR-14 event dispatching for individual item filtering.
     */
    public function testTocItemFilterEventIsDispatchedPerItem(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Header 1', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Header 2', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        $config = new TocConfiguration(mode: 'sectionIndexOnly');

        // Expect 4 events: Before + 2x TocItemFilter + After
        $this->mockEventDispatcher->expects(static::exactly(4))
            ->method('dispatch')
            ->willReturnCallback(static function (object $event): object {
                static::assertTrue(
                    $event instanceof BeforeTocItemsBuiltEvent
                    || $event instanceof TocItemFilterEvent
                    || $event instanceof AfterTocItemsBuiltEvent,
                    'Expected one of the TOC events'
                );

                return $event;
            });

        $this->service->buildForPagesWithConfig([1], $config);
    }

    /**
     * Test that TocItemFilterEvent can skip items.
     *
     * Verifies that marking an item as skipped removes it from the final TOC.
     */
    public function testTocItemFilterEventCanSkipItems(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Keep This', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Skip This', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        $config = new TocConfiguration(mode: 'sectionIndexOnly');

        // Skip items with title "Skip This"
        $this->mockEventDispatcher->method('dispatch')
            ->willReturnCallback(static function (object $event): object {
                if ($event instanceof TocItemFilterEvent && 'Skip This' === $event->getItem()->title) {
                    $event->skip();
                }

                return $event;
            });

        $toc = $this->service->buildForPagesWithConfig([1], $config);

        static::assertCount(1, $toc);
        static::assertEquals('Keep This', $toc[0]->title);
    }

    /**
     * Test that BeforeTocItemsBuiltEvent can modify configuration.
     *
     * Verifies that event listeners can change the TOC configuration dynamically.
     */
    public function testBeforeTocItemsBuiltEventCanModifyConfig(): void
    {
        $this->mockRepo->method('findByPages')->willReturn([
            ['uid' => 1, 'header' => 'Header 1', 'colPos' => 0, 'sectionIndex' => 0, 'sorting' => 256, 'CType' => 'text', 'header_layout' => 0],
            ['uid' => 2, 'header' => 'Header 2', 'colPos' => 0, 'sectionIndex' => 1, 'sorting' => 512, 'CType' => 'text', 'header_layout' => 0],
        ]);
        $this->mockRepo->method('findAllContainerChildrenForPages')->willReturn([]);
        $this->mockContainerCheck->method('isContainer')->willReturn(false);

        // Start with 'visibleHeaders' mode
        $config = new TocConfiguration(mode: 'visibleHeaders');

        // Event listener changes mode to 'sectionIndexOnly'
        $this->mockEventDispatcher->method('dispatch')
            ->willReturnCallback(static function (object $event): object {
                if ($event instanceof BeforeTocItemsBuiltEvent) {
                    $newConfig = new TocConfiguration(mode: 'sectionIndexOnly');
                    $event->setConfig($newConfig);
                }

                return $event;
            });

        $toc = $this->service->buildForPagesWithConfig([1], $config);

        // Should only get 1 item (Header 2) because mode was changed to sectionIndexOnly
        static::assertCount(1, $toc);
        static::assertEquals('Header 2', $toc[0]->title);
    }
}
