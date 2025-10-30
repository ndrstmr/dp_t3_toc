<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Tests\Unit\DataProcessing;

use Ndrstmr\DpT3Toc\DataProcessing\TocProcessor;
use Ndrstmr\DpT3Toc\Domain\Model\TocItem;
use Ndrstmr\DpT3Toc\Service\TocBuilderServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class TocProcessorTest extends TestCase
{
    private TocProcessor $processor;

    /** @var MockObject&TocBuilderServiceInterface */
    private $mockService;

    /** @var MockObject&ContentObjectRenderer */
    private $mockCObj;

    protected function setUp(): void
    {
        $this->mockService = $this->createMock(TocBuilderServiceInterface::class);
        $this->mockCObj = $this->createMock(ContentObjectRenderer::class);
        $this->processor = new TocProcessor($this->mockService);
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
            ->method('buildForPage')
            ->with(42, 'sectionIndexOnly', null, null, 0, 0)
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

        $this->mockService
            ->expects(static::once())
            ->method('buildForPage')
            ->with(0, 'ffMode', null, null, 5, 0)
            ->willReturn([]);
        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process(
            $this->mockCObj,
            [],
            ['mode' => 'tsMode', 'maxDepth' => '99'],
            [
                'tocSettings' => [
                    'settings' => [
                        'mode' => 'ffMode',
                        'maxDepth' => 5,
                    ],
                ],
            ]
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

        $this->mockService
            ->expects(static::once())
            ->method('buildForPage')
            ->with(0, 'visibleHeaders', null, null, 0, 999)
            ->willReturn([]);
        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process(
            $this->mockCObj,
            [],
            [],
            ['data' => ['uid' => 999]]
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

        $this->mockService
            ->expects(static::once())
            ->method('buildForPage')
            ->with(0, 'visibleHeaders', null, null, 0, 0)
            ->willReturn([]);
        $this->mockService->method('sortItems')->willReturn([]);

        $this->processor->process(
            $this->mockCObj,
            [],
            ['maxDepth' => '0'], // TS-Config
            []
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
}
