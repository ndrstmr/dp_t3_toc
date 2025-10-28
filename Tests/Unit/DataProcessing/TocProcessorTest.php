<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Tests\Unit\DataProcessing;

use Ndrstmr\DpT3Toc\DataProcessing\TocProcessor;
use Ndrstmr\DpT3Toc\Domain\Model\TocItem;
use Ndrstmr\DpT3Toc\Service\TocBuilderService;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class TocProcessorTest extends TestCase
{
    private TocProcessor $processor;
    private TocBuilderService $mockService;
    private ContentObjectRenderer $mockCObj;

    protected function setUp(): void
    {
        $this->mockService = $this->createMock(TocBuilderService::class);
        $this->mockCObj = $this->createMock(ContentObjectRenderer::class);
        $this->processor = new TocProcessor($this->mockService);
    }

    public function testProcessCallsServiceAndTransformsResult(): void
    {
        $mockItems = [
            new TocItem(
                data: ['uid' => 1, 'header' => 'Header 1', 'colPos' => 0],
                title: 'Header 1',
                anchor: '#c1',
                level: 2
            ),
            new TocItem(
                data: ['uid' => 2, 'header' => 'Header 2', 'colPos' => 0],
                title: 'Header 2',
                anchor: '#c2',
                level: 2
            ),
        ];

        $this->mockCObj->method('stdWrapValue')->willReturn('this');

        $this->mockService
            ->expects($this->once())
            ->method('buildForPage')
            ->with(0, 'sectionIndexOnly', null, null, 0)
            ->willReturn($mockItems);

        $this->mockService
            ->expects($this->once())
            ->method('sortItems')
            ->with($mockItems)
            ->willReturn($mockItems);

        $result = $this->processor->process(
            $this->mockCObj,
            [],
            [
                'as' => 'tocItems',
                'mode' => 'sectionIndexOnly',
                'pidInList' => 'this',
                'includeColPos' => '',
                'excludeColPos' => '',
            ],
            []
        );

        $this->assertArrayHasKey('tocItems', $result);
        $this->assertCount(2, $result['tocItems']);
        $this->assertEquals('Header 1', $result['tocItems'][0]['title']);
        $this->assertEquals('#c1', $result['tocItems'][0]['anchor']);
    }

    public function testProcessUsesDefaultValues(): void
    {
        $this->mockCObj->method('stdWrapValue')->willReturn('this');

        $this->mockService
            ->method('buildForPage')
            ->willReturn([]);

        $this->mockService
            ->method('sortItems')
            ->willReturn([]);

        $result = $this->processor->process(
            $this->mockCObj,
            [],
            [], // No config = defaults
            []
        );

        $this->assertArrayHasKey('tocItems', $result);
        $this->assertIsArray($result['tocItems']);
    }

    public function testProcessPassesExcludeColPos(): void
    {
        $this->mockCObj->method('stdWrapValue')->willReturn('this');

        $this->mockService
            ->expects($this->once())
            ->method('buildForPage')
            ->with(
                $this->anything(),
                $this->anything(),
                null, // includeColPos (empty string = null)
                [5, 88], // excludeColPos
                0
            )
            ->willReturn([]);

        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process(
            $this->mockCObj,
            [],
            [
                'excludeColPos' => '5,88',
                'includeColPos' => '',
            ],
            []
        );
    }

    public function testProcessPassesIncludeColPos(): void
    {
        $this->mockCObj->method('stdWrapValue')->willReturn('this');

        $this->mockService
            ->expects($this->once())
            ->method('buildForPage')
            ->with(
                $this->anything(),
                $this->anything(),
                [0, 1, 2, 3, 4], // includeColPos
                null, // excludeColPos (empty = null)
                0
            )
            ->willReturn([]);

        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process(
            $this->mockCObj,
            [],
            [
                'includeColPos' => '0,1,2,3,4',
                'excludeColPos' => '',
            ],
            []
        );
    }

    public function testNormalizeColPosFilterWithEmptyString(): void
    {
        $this->mockCObj->method('stdWrapValue')->willReturn('this');

        $this->mockService
            ->expects($this->once())
            ->method('buildForPage')
            ->with(
                $this->anything(),
                $this->anything(),
                null, // Empty string = null
                null,
                0
            )
            ->willReturn([]);

        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process(
            $this->mockCObj,
            [],
            [
                'includeColPos' => '',
                'excludeColPos' => '',
            ],
            []
        );
    }

    public function testNormalizeColPosFilterWithWildcard(): void
    {
        $this->mockCObj->method('stdWrapValue')->willReturn('this');

        $this->mockService
            ->expects($this->once())
            ->method('buildForPage')
            ->with(
                $this->anything(),
                $this->anything(),
                null, // Wildcard = null
                null,
                0
            )
            ->willReturn([]);

        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process(
            $this->mockCObj,
            [],
            [
                'includeColPos' => '*',
                'excludeColPos' => '',
            ],
            []
        );
    }
}
