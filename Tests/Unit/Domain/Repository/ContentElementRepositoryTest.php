<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Tests\Unit\Domain\Repository;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Ndrstmr\DpT3Toc\Domain\Repository\ContentElementRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;

final class ContentElementRepositoryTest extends TestCase
{
    private ContentElementRepository $repository;
    private MockObject&ConnectionPool $mockConnectionPool;
    private MockObject&FrontendRestrictionContainer $mockRestrictions;
    private MockObject&QueryBuilder $mockQueryBuilder;
    private MockObject&ExpressionBuilder $mockExpressionBuilder;

    protected function setUp(): void
    {
        $this->mockConnectionPool = $this->createMock(ConnectionPool::class);
        $this->mockRestrictions = $this->createMock(FrontendRestrictionContainer::class);
        $this->mockQueryBuilder = $this->createMock(QueryBuilder::class);
        $this->mockExpressionBuilder = $this->createMock(ExpressionBuilder::class);

        $this->repository = new ContentElementRepository(
            $this->mockConnectionPool,
            $this->mockRestrictions
        );
    }

    public function testFindByPageReturnsEmptyArrayForZeroUid(): void
    {
        $result = $this->repository->findByPage(0);

        static::assertSame([], $result);
    }

    public function testFindByPageReturnsEmptyArrayForNegativeUid(): void
    {
        $result = $this->repository->findByPage(-1);

        static::assertSame([], $result);
    }

    public function testFindByPageExecutesQueryWithCorrectParameters(): void
    {
        $pageUid = 42;
        $expectedRows = [
            ['uid' => 1, 'header' => 'Test 1', 'colPos' => 0],
            ['uid' => 2, 'header' => 'Test 2', 'colPos' => 1],
        ];

        $mockResult = $this->createMock(Result::class);
        $mockResult->method('fetchAllAssociative')->willReturn($expectedRows);

        // Mock CompositeExpression for OR condition
        $mockOrExpression = $this->createMock(CompositeExpression::class);

        $this->mockExpressionBuilder
            ->method('eq')
            ->willReturnCallback(function (string $field, string $param): string {
                return "{$field} = {$param}";
            });

        $this->mockExpressionBuilder
            ->method('isNull')
            ->willReturnCallback(static fn (string $field): string => "{$field} IS NULL");

        $this->mockExpressionBuilder
            ->method('or')
            ->willReturn($mockOrExpression);

        $this->mockQueryBuilder
            ->method('expr')
            ->willReturn($this->mockExpressionBuilder);

        $this->mockQueryBuilder
            ->method('select')
            ->with('*')
            ->willReturnSelf();

        $this->mockQueryBuilder
            ->method('from')
            ->with('tt_content')
            ->willReturnSelf();

        $this->mockQueryBuilder
            ->method('where')
            ->willReturnSelf();

        $this->mockQueryBuilder
            ->method('orderBy')
            ->with('sorting', 'ASC')
            ->willReturnSelf();

        $this->mockQueryBuilder
            ->method('executeQuery')
            ->willReturn($mockResult);

        $this->mockQueryBuilder
            ->method('createNamedParameter')
            ->willReturnCallback(static fn (int $value, ParameterType|int $type = ParameterType::INTEGER): string => ":{$value}");

        $this->mockQueryBuilder
            ->method('setRestrictions')
            ->with($this->mockRestrictions)
            ->willReturnSelf();

        $this->mockConnectionPool
            ->method('getQueryBuilderForTable')
            ->with('tt_content')
            ->willReturn($this->mockQueryBuilder);

        $result = $this->repository->findByPage($pageUid);

        static::assertSame($expectedRows, $result);
    }

    public function testFindByPageSetsRestrictions(): void
    {
        $mockResult = $this->createMock(Result::class);
        $mockResult->method('fetchAllAssociative')->willReturn([]);

        $mockOrExpression = $this->createMock(CompositeExpression::class);

        $this->mockExpressionBuilder->method('eq')->willReturn('expr');
        $this->mockExpressionBuilder->method('isNull')->willReturn('expr');
        $this->mockExpressionBuilder->method('or')->willReturn($mockOrExpression);

        $this->mockQueryBuilder->method('expr')->willReturn($this->mockExpressionBuilder);
        $this->mockQueryBuilder->method('select')->willReturnSelf();
        $this->mockQueryBuilder->method('from')->willReturnSelf();
        $this->mockQueryBuilder->method('where')->willReturnSelf();
        $this->mockQueryBuilder->method('orderBy')->willReturnSelf();
        $this->mockQueryBuilder->method('executeQuery')->willReturn($mockResult);
        $this->mockQueryBuilder->method('createNamedParameter')->willReturn(':param');

        $this->mockQueryBuilder
            ->expects(static::once())
            ->method('setRestrictions')
            ->with($this->mockRestrictions)
            ->willReturnSelf();

        $this->mockConnectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($this->mockQueryBuilder);

        $this->repository->findByPage(1);
    }

    public function testFindContainerChildrenReturnsEmptyArrayForZeroUid(): void
    {
        $result = $this->repository->findContainerChildren(0);

        static::assertSame([], $result);
    }

    public function testFindContainerChildrenReturnsEmptyArrayForNegativeUid(): void
    {
        $result = $this->repository->findContainerChildren(-1);

        static::assertSame([], $result);
    }

    public function testFindContainerChildrenExecutesQueryWithCorrectParameters(): void
    {
        $parentUid = 10;
        $expectedRows = [
            ['uid' => 21, 'header' => 'Child 1', 'tx_container_parent' => 10],
            ['uid' => 22, 'header' => 'Child 2', 'tx_container_parent' => 10],
        ];

        $mockResult = $this->createMock(Result::class);
        $mockResult->method('fetchAllAssociative')->willReturn($expectedRows);

        $this->mockExpressionBuilder
            ->method('eq')
            ->willReturn('expr');

        $this->mockQueryBuilder
            ->method('expr')
            ->willReturn($this->mockExpressionBuilder);

        $this->mockQueryBuilder
            ->method('select')
            ->with('*')
            ->willReturnSelf();

        $this->mockQueryBuilder
            ->method('from')
            ->with('tt_content')
            ->willReturnSelf();

        $this->mockQueryBuilder
            ->method('where')
            ->willReturnSelf();

        $this->mockQueryBuilder
            ->method('orderBy')
            ->with('colPos', 'ASC')
            ->willReturnSelf();

        $this->mockQueryBuilder
            ->method('addOrderBy')
            ->with('sorting', 'ASC')
            ->willReturnSelf();

        $this->mockQueryBuilder
            ->method('executeQuery')
            ->willReturn($mockResult);

        $this->mockQueryBuilder
            ->method('createNamedParameter')
            ->with($parentUid)
            ->willReturn(':10');

        $this->mockQueryBuilder
            ->method('setRestrictions')
            ->with($this->mockRestrictions)
            ->willReturnSelf();

        $this->mockConnectionPool
            ->method('getQueryBuilderForTable')
            ->with('tt_content')
            ->willReturn($this->mockQueryBuilder);

        $result = $this->repository->findContainerChildren($parentUid);

        static::assertSame($expectedRows, $result);
    }

    public function testFindContainerChildrenOrdersByColPosThenSorting(): void
    {
        $mockResult = $this->createMock(Result::class);
        $mockResult->method('fetchAllAssociative')->willReturn([]);

        $this->mockExpressionBuilder->method('eq')->willReturn('expr');
        $this->mockQueryBuilder->method('expr')->willReturn($this->mockExpressionBuilder);
        $this->mockQueryBuilder->method('select')->willReturnSelf();
        $this->mockQueryBuilder->method('from')->willReturnSelf();
        $this->mockQueryBuilder->method('where')->willReturnSelf();
        $this->mockQueryBuilder->method('executeQuery')->willReturn($mockResult);
        $this->mockQueryBuilder->method('createNamedParameter')->willReturn(':param');
        $this->mockQueryBuilder->method('setRestrictions')->willReturnSelf();

        $this->mockQueryBuilder
            ->expects(static::once())
            ->method('orderBy')
            ->with('colPos', 'ASC')
            ->willReturnSelf();

        $this->mockQueryBuilder
            ->expects(static::once())
            ->method('addOrderBy')
            ->with('sorting', 'ASC')
            ->willReturnSelf();

        $this->mockConnectionPool
            ->method('getQueryBuilderForTable')
            ->willReturn($this->mockQueryBuilder);

        $this->repository->findContainerChildren(1);
    }
}
