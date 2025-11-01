<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Tests\Unit\DataProcessing;

use Ndrstmr\DpT3Toc\DataProcessing\TocProcessor;
use Ndrstmr\DpT3Toc\Domain\Model\TocItem;
use Ndrstmr\DpT3Toc\Service\TocBuilderServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class TocProcessorTest extends TestCase
{
    private TocProcessor $processor;

    /** @var MockObject&TocBuilderServiceInterface */
    private $mockService;

    /** @var MockObject&ContentObjectRenderer */
    private $mockCObj;

    /** @var MockObject&LoggerInterface */
    private $mockLogger;

    protected function setUp(): void
    {
        $this->mockService = $this->createMock(TocBuilderServiceInterface::class);
        $this->mockCObj = $this->createMock(ContentObjectRenderer::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->processor = new TocProcessor($this->mockService, $this->mockLogger);
    }

    public function testProcessCallsServiceAndTransformsResult(): void
    {
        $mockItems = [
            new TocItem(data: ['uid' => 1], title: 'Header 1', anchor: '#c1', level: 2),
            new TocItem(data: ['uid' => 2], title: 'Header 2', anchor: '#c2', level: 2),
        ];

        // Use direct page UID instead of 'this' to avoid PageInformation mocking complexity
        $processorConfig = [
            'as' => 'tocItems',
            'mode' => 'sectionIndexOnly',
            'pidInList' => '42',
            'includeColPos' => '',
            'excludeColPos' => '',
        ];

        $this->mockCObj->method('stdWrapValue')->willReturnCallback(
            fn (string $key, array $config) => match ($key) {
                'pidInList' => '42',
                'mode' => 'sectionIndexOnly',
                'includeColPos' => '',
                'excludeColPos' => '',
                'maxDepth' => null,
                default => null,
            }
        );

        $this->mockService
            ->expects(static::once())
            ->method('buildForPages')
            ->with([42], 'sectionIndexOnly', null, null, 0, 0)
            ->willReturn($mockItems);

        $this->mockService
            ->expects(static::once())
            ->method('sortItems')
            ->with($mockItems)
            ->willReturn($mockItems);

        $result = $this->processor->process(
            $this->mockCObj,
            [],
            $processorConfig,
            []
        );

        static::assertArrayHasKey('tocItems', $result);
        $tocItems = $result['tocItems'];
        static::assertIsArray($tocItems);
        static::assertCount(2, $tocItems);
        static::assertIsArray($tocItems[0]);
        static::assertArrayHasKey('title', $tocItems[0]);
        static::assertEquals('Header 1', $tocItems[0]['title']);
        static::assertEquals('#c1', $tocItems[0]['anchor']);
    }

    public function testProcessOverridesConfigWithFlexForm(): void
    {
        $this->mockCObj->method('stdWrapValue')->willReturnMap([
            ['pidInList', static::anything(), 'this'], // TS Fallback
            ['mode', static::anything(), 'tsMode'], // TS Fallback
            ['maxDepth', static::anything(), '99'], // TS Fallback
        ]);

        // Mock fallback chain for pidInList='this'
        // Provide processedData with data.pid for Fallback 2
        $processedData = [
            'tocSettings' => [
                'settings' => [
                    'mode' => 'ffMode',
                    'maxDepth' => 5,
                ],
            ],
            'data' => ['pid' => 42],
        ];

        $this->mockService
            ->expects(static::once())
            ->method('buildForPages')
            ->with([42], 'ffMode', null, null, 5, 0)
            ->willReturn([]);
        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process(
            $this->mockCObj,
            [],
            ['mode' => 'tsMode', 'maxDepth' => '99'],
            $processedData
        );
    }

    public function testProcessPassesCurrentUidToService(): void
    {
        $this->mockCObj->method('stdWrapValue')->willReturnMap([
            ['pidInList', static::anything(), 'this'],
            ['mode', static::anything(), null],
            ['includeColPos', static::anything(), null],
            ['excludeColPos', static::anything(), null],
            ['maxDepth', static::anything(), null],
        ]);

        // Mock fallback chain for pidInList='this'
        // Fallback 2: data.pid will be used (no PageInformation, but processedData exists)

        $this->mockService
            ->expects(static::once())
            ->method('buildForPages')
            ->with([50], 'visibleHeaders', null, null, 0, 999)
            ->willReturn([]);
        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process(
            $this->mockCObj,
            [],
            [],
            ['data' => ['uid' => 999, 'pid' => 50]]
        );
    }

    public function testProcessHandlesZeroAsValidTypoScriptValue(): void
    {
        $this->mockCObj->method('stdWrapValue')->willReturnMap([
            ['pidInList', static::anything(), 'this'],
            ['mode', static::anything(), null],
            ['includeColPos', static::anything(), null],
            ['excludeColPos', static::anything(), null],
            ['maxDepth', static::anything(), '0'],
        ]);

        // Mock fallback chain for pidInList='this'
        // Fallback 2: data.pid (no PageInformation)

        $this->mockService
            ->expects(static::once())
            ->method('buildForPages')
            ->with([100], 'visibleHeaders', null, null, 0, 0)
            ->willReturn([]);
        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process(
            $this->mockCObj,
            [],
            ['maxDepth' => '0'], // TS-Config
            ['data' => ['pid' => 100]]
        );
    }

    /**
     * @param list<int>|null $expected
     */
    #[DataProvider('colPosFilterDataProvider')]
    public function testNormalizeColPosFilter(string $input, ?array $expected): void
    {
        $method = new \ReflectionMethod(TocProcessor::class, 'normalizeColPosFilter');

        $result = $method->invoke($this->processor, $input);

        static::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{0: string, 1: ?list<int>}>
     */
    public static function colPosFilterDataProvider(): array
    {
        return [
            'empty string' => ['', null],
            'wildcard' => ['*', null],
            'single value' => ['1', [1]],
            'multiple values' => ['0,1,5', [0, 1, 5]],
            'with whitespace' => [' 1, 2, 3 ', [1, 2, 3]],
            'with double commas' => ['1,,3', [1, 3]],
            'only commas' => [',,', null],
        ];
    }

    // ========================================
    // Tests for v4.0.0 Multi-Page Support
    // ========================================

    public function testProcessHandlesMultiplePageUidsInPidInList(): void
    {
        $mockItems = [
            new TocItem(data: ['uid' => 1, 'pid' => 42], title: 'Header from page 42', anchor: '#c1', level: 2),
            new TocItem(data: ['uid' => 2, 'pid' => 43], title: 'Header from page 43', anchor: '#c2', level: 2),
        ];

        $processorConfig = [
            'as' => 'tocItems',
            'pidInList' => '42,43',
        ];

        $this->mockCObj->method('stdWrapValue')->willReturnCallback(
            fn (string $key, array $config) => match ($key) {
                'pidInList' => '42,43',
                'mode' => null,
                'includeColPos' => null,
                'excludeColPos' => null,
                'maxDepth' => null,
                default => null,
            }
        );

        $this->mockService
            ->expects(static::once())
            ->method('buildForPages')
            ->with([42, 43], 'visibleHeaders', null, null, 0, 0)
            ->willReturn($mockItems);

        $this->mockService
            ->expects(static::once())
            ->method('sortItems')
            ->with($mockItems)
            ->willReturn($mockItems);

        $result = $this->processor->process(
            $this->mockCObj,
            [],
            $processorConfig,
            []
        );

        static::assertArrayHasKey('tocItems', $result);
        $tocItems = $result['tocItems'];
        static::assertIsArray($tocItems);
        static::assertCount(2, $tocItems);
        static::assertIsArray($tocItems[0]);
        static::assertIsArray($tocItems[1]);
        static::assertArrayHasKey('title', $tocItems[0]);
        static::assertArrayHasKey('title', $tocItems[1]);
        static::assertEquals('Header from page 42', $tocItems[0]['title']);
        static::assertEquals('Header from page 43', $tocItems[1]['title']);
    }

    public function testProcessHandlesPidInListWithWhitespace(): void
    {
        $this->mockCObj->method('stdWrapValue')->willReturnCallback(
            fn (string $key) => match ($key) {
                'pidInList' => ' 10, 20 , 30 ',
                default => null,
            }
        );

        $this->mockService
            ->expects(static::once())
            ->method('buildForPages')
            ->with([10, 20, 30], 'visibleHeaders', null, null, 0, 0)
            ->willReturn([]);

        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process($this->mockCObj, [], [], []);
    }

    public function testProcessFiltersOutInvalidPidsInPidInList(): void
    {
        $this->mockCObj->method('stdWrapValue')->willReturnCallback(
            fn (string $key) => match ($key) {
                'pidInList' => '0,42,-5,43,abc',
                default => null,
            }
        );

        // Should only use [42, 43] (filter out 0, -5, and non-numeric 'abc')
        $this->mockService
            ->expects(static::once())
            ->method('buildForPages')
            ->with([42, 43], 'visibleHeaders', null, null, 0, 0)
            ->willReturn([]);

        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process($this->mockCObj, [], [], []);
    }

    public function testProcessHandlesEmptyPidInListAsCurrent(): void
    {
        $this->mockCObj->method('stdWrapValue')->willReturnCallback(
            fn (string $key) => match ($key) {
                'pidInList' => '',
                default => null,
            }
        );

        // Mock fallback chain for empty pidInList
        // Fallback 2: data.pid (no PageInformation)

        $this->mockService
            ->expects(static::once())
            ->method('buildForPages')
            ->with([77], 'visibleHeaders', null, null, 0, 0)
            ->willReturn([]);

        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process($this->mockCObj, [], [], ['data' => ['pid' => 77]]);
    }

    /**
     * Test the fallback chain for resolving current page UID.
     *
     * Fallback order:
     * 1. PageInformation (not available in unit tests)
     * 2. $processedData['data']['pid'] (tested here)
     */
    public function testResolvePageUidFallbackChain(): void
    {
        $this->mockCObj->method('stdWrapValue')->willReturnCallback(
            fn (string $key) => match ($key) {
                'pidInList' => 'this',
                default => null,
            }
        );

        // Fallback 2 should be used: data.pid = 88
        $this->mockService
            ->expects(static::once())
            ->method('buildForPages')
            ->with([88], 'visibleHeaders', null, null, 0, 0)
            ->willReturn([]);

        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process(
            $this->mockCObj,
            [],
            [],
            ['data' => ['pid' => 88]]
        );
    }

    /**
     * Test that all fallbacks fail gracefully.
     */
    public function testResolvePageUidReturnsZeroWhenAllFallbacksFail(): void
    {
        $this->mockCObj->method('stdWrapValue')->willReturnCallback(
            fn (string $key) => match ($key) {
                'pidInList' => 'this',
                default => null,
            }
        );

        // No PageInformation, no data.pid → should return [0]
        $this->mockService
            ->expects(static::once())
            ->method('buildForPages')
            ->with([0], 'visibleHeaders', null, null, 0, 0)
            ->willReturn([]);

        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process(
            $this->mockCObj,
            [],
            [],
            [] // No data
        );
    }
}
