<?php

namespace Filter\FilterBundle\Service\Filter;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

/**
 * Interface Action
 * @package Filter\FilterBundle\Service\Filter
 */
interface Action
{
	/**
	 * @param QueryBuilder $qb
	 * @param callable $alias
	 * @param Property $root
	 * @return mixed
	 */
	public function prepare(QueryBuilder $qb, callable $alias, Property $root);

	/**
	 * @param Query $query
	 * @return mixed
	 */
	public function execute(Query $query);
}
