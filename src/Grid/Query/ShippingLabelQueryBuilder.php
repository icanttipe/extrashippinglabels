<?php

namespace PrestaShop\Module\ExtraShippingLabels\Grid\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\Module\ExtraShippingLabels\Filters\ShippingLabelFilters;
use PrestaShop\PrestaShop\Core\Grid\Query\DoctrineQueryBuilderInterface;
use PrestaShop\PrestaShop\Core\Grid\Search\SearchCriteriaInterface;

/**
 * Builds queries for the shipping labels grid.
 */
class ShippingLabelQueryBuilder implements DoctrineQueryBuilderInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $dbPrefix;

    /**
     * @param Connection $connection
     * @param string $dbPrefix
     */
    public function __construct(Connection $connection, string $dbPrefix)
    {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return QueryBuilder
     */
    public function getSearchQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        /** @var ShippingLabelFilters $filters */
        $filters = $searchCriteria;

        $qb = $this->getBaseQuery();

        $this->applyFilters($qb, $filters->getFilters());

        $qb->orderBy(
            'sl.' . $filters->getOrderBy(),
            $filters->getOrderWay()
        )
            ->setFirstResult($filters->getOffset() ?? 0)
            ->setMaxResults($filters->getLimit() ?? 20);

        return $qb;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return QueryBuilder
     */
    public function getCountQueryBuilder(SearchCriteriaInterface $searchCriteria): QueryBuilder
    {
        /** @var ShippingLabelFilters $filters */
        $filters = $searchCriteria;

        $qb = $this->getBaseQuery();
        $qb->select('COUNT(sl.id_shipping_label)');

        $this->applyFilters($qb, $filters->getFilters());

        return $qb;
    }

    /**
     * @return QueryBuilder
     */
    private function getBaseQuery(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('sl.id_shipping_label, sl.id_order, sl.tracking_number, sl.module_name, sl.date_add')
            ->from($this->dbPrefix . 'shipping_label', 'sl');
    }

    /**
     * @param QueryBuilder $qb
     * @param array $filters
     */
    private function applyFilters(QueryBuilder $qb, array $filters)
    {
        foreach ($filters as $filterName => $filterValue) {
            if ('id_shipping_label' === $filterName) {
                $qb->andWhere('sl.id_shipping_label = :id_shipping_label');
                $qb->setParameter('id_shipping_label', $filterValue);
                continue;
            }
            if ('id_order' === $filterName) {
                $qb->andWhere('sl.id_order = :id_order');
                $qb->setParameter('id_order', $filterValue);
                continue;
            }
            if (in_array($filterName, ['tracking_number', 'module_name', 'date_add'])) {
                $qb->andWhere(sprintf('sl.%s LIKE :%s', $filterName, $filterName));
                $qb->setParameter($filterName, '%' . $filterValue . '%');
            }
        }
    }
}
