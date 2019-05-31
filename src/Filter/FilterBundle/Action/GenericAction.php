<?php

namespace Filter\FilterBundle\Action;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Filter\FilterBundle\Service\Filter\Property;
use Filter\FilterBundle\Service\Filter\Action;

/**
 * Class GenericAction
 * @package Filter\FilterBundle\Action
 */
class GenericAction implements Action
{
	/**
	 * @var callable
	 */
	private $prepare;
	/**
	 * @var callable
	 */
	private $execute;

	/**
	 * GenericAction constructor.
	 * @param callable $prepare
	 * @param callable $execute
	 */
	public function __construct(callable $prepare, callable $execute)
    {
        $this->prepare = $prepare;
        $this->execute = $execute;
    }

	/**
	 * @param QueryBuilder $qb
	 * @param callable $alias
	 * @param Property $root
	 * @return mixed
	 */
	public function prepare(QueryBuilder $qb, callable $alias, Property $root)
    {
        $prepare = $this->prepare;
        return $prepare($qb, $alias, $root);
    }

	/**
	 * @param Query $query
	 * @return mixed
	 */
	public function execute(Query $query)
    {
        $execute = $this->execute;
        return $execute($query);
    }
}
