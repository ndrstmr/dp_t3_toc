<?php

declare(strict_types=1);

namespace Ndrstmr\DpT3Toc\Domain\Repository;

use Doctrine\DBAL\ParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;

/**
 * Repository for fetching content elements from the database.
 */
final readonly class ContentElementRepository implements ContentElementRepositoryInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private FrontendRestrictionContainer $restrictions,
    ) {
    }

    /**
     * @return list<array<string, mixed>> Array of tt_content rows
     */
    public function findByPage(int $pageUid): array
    {
        if ($pageUid <= 0) {
            return [];
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $qb->setRestrictions($this->restrictions);

        $rows = $qb->select('*')
            ->from('tt_content')
            ->where(
                $qb->expr()->eq('pid', $qb->createNamedParameter($pageUid, ParameterType::INTEGER)),
                $qb->expr()->or(
                    $qb->expr()->eq('tx_container_parent', $qb->createNamedParameter(0, ParameterType::INTEGER)),
                    $qb->expr()->isNull('tx_container_parent')
                )
            )
            ->orderBy('sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return $rows;
    }

    /**
     * @return list<array<string, mixed>> Array of tt_content rows
     */
    public function findContainerChildren(int $parentUid): array
    {
        if ($parentUid <= 0) {
            return [];
        }

        $qb = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $qb->setRestrictions($this->restrictions);

        $rows = $qb->select('*')
            ->from('tt_content')
            ->where($qb->expr()->eq('tx_container_parent', $qb->createNamedParameter($parentUid)))
            ->orderBy('colPos', 'ASC')
            ->addOrderBy('sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return $rows;
    }
}
