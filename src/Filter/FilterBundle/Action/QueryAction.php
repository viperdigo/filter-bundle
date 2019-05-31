<?php

namespace Filter\FilterBundle\Action;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Knp\Component\Pager\Event\Subscriber\Paginate\Doctrine\ORM\QuerySubscriber\UsesPaginator;
use Filter\FilterBundle\Service\Filter\Property;
use Filter\FilterBundle\Service\Filter\CountableAction;

/**
 * Class QueryAction
 * @package Filter\FilterBundle\Action
 */
class QueryAction implements CountableAction
{
	/**
	 * @var
	 */
	private $paginator;
	/**
	 * @var
	 */
	private $request;
	/**
	 * @var
	 */
	private $pages;
	/**
	 * @var
	 */
	private $forcePartialLoad;
	/**
	 * @var
	 */
	private $identifier;
	/**
	 * @var
	 */
	private $qb;
	/**
	 * @var
	 */
	private $count;

	/**
	 * QueryAction constructor.
	 * @param $paginator
	 * @param $request
	 * @param $pages
	 * @param $forcePartialLoad
	 */
	public function __construct($paginator, $request, $pages, $forcePartialLoad)
    {
        $this->paginator = $paginator;
        $this->request = $request;
        $this->pages = $pages;
    }

	/**
	 * @param QueryBuilder $qb
	 * @param callable $alias
	 * @param Property $root
	 */
	public function prepare(QueryBuilder $qb, callable $alias, Property $root)
    {
        $qb->select($qb->getDQLPart('from')[0]->getAlias());
        $this->qb = $qb;
        $this->identifier = $root->getMetadata()->getClassMetadata()->getIdentifier();
    }

	/**
	 * @param Query $query
	 * @return array
	 */
	public function execute(Query $query)
    {
        if (!$this->pages) {
            $result = $query->getResult();
            $this->count = count($result);

            return $result;
        }

        $page = $this->request->get('page', 1);

        if (count($this->identifier) > 1) {
            $count = $this->getCountQuery($query)->getSingleScalarResult();
            $query->setHint('knp_paginator.count', $count);
            $query->setHint(UsesPaginator::HINT_FETCH_JOIN_COLLECTION, false);
//            $options = array('wrap-queries' => true);
        } else {
//            $options = array('wrap-queries' => true);
        }

        if ($this->forcePartialLoad) {
            $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        }

//        $result = $this->paginator->paginate($query, $page, $this->pages, $options);
        $result = $this->paginator->paginate($query, $page, $this->pages);

        $this->count = $result->getTotalItemCount();

        return $result;
    }

	/**
	 * @return mixed
	 */
	public function count()
    {
        return $this->count;
    }

	/**
	 * @param Query $query
	 * @return mixed
	 */
	private function getCountQuery(Query $query)
    {
        $qb = clone $this->qb;

        $qb->select(sprintf('COUNT(IDENTITY(%s)) AS qtd', $qb->getDQLPart('from')[0]->getAlias()));

        $countQuery = $qb->getQuery();
        if ($query->getResultCacheId()) {
            $countQuery->useResultCache(
                true,
                $query->getResultCacheLifetime(),
                'count-'.$query->getResultCacheId()
            );
        }

        return $countQuery;
    }
}
