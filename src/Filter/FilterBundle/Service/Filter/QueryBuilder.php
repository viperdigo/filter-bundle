<?php

namespace Filter\FilterBundle\Service\Filter;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;

/**
 * Class QueryBuilder
 * @package Filter\FilterBundle\Service\Filter
 */
class QueryBuilder
{
	/**
	 * @var EntityManager
	 */
	private $em;
	/**
	 * @var
	 */
	private $qb;
	/**
	 * @var
	 */
	private $root;
	/**
	 * @var array
	 */
	private $orders = array();
	/**
	 * @var
	 */
	private $filter;

	/**
	 * @var array
	 */
	private $joinMemo = array();

	/**
	 * QueryBuilder constructor.
	 * @param EntityManager $em
	 */
	public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

	/**
	 * @return mixed
	 */
	public function getQueryBuilder()
    {
        return $this->qb;
    }

	/**
	 * @param Property $root
	 * @param Filter $filter
	 * @param $queryFragments
	 */
	public function build(Property $root, Filter $filter, $queryFragments)
    {
        $this->qb = $this->em->createQueryBuilder();
        $this->root = $root;
        $this->qb->from($root->getMetadata()->getClass(), $this->alias($root));
        $this->filter = $filter;

        $this->buildEagerLoads($root);
        $this->buildQuery($root);
        $this->buildOrdersArray();
        $this->appendQueryFragments($root, $queryFragments);
    }

	/**
	 * @param Property $root
	 */
	private function buildEagerLoads(Property $root)
    {
        $children = $root->getChildren();

        foreach ($children as $child) {
            if ($child->getEagerLoad()) {
                $this->qb->addSelect($this->alias($child));
                $this->buildJoin($child);
            }

            $this->buildEagerLoads($child);
        }
    }

	/**
	 * @param Property $root
	 */
	private function buildQuery(Property $root)
    {
        $children = $root->getChildren();

        foreach ($children as $child) {
            if (count($child->getChildren()) > 0) {
                if (!$child->getEagerLoad()) {
                    $this->buildJoin($child);
                }
            }

            if (!is_null($child->getField())) {
                $this->buildWhere($child);
            }

            if (!is_null($child->getOrder())) {
                $this->buildOrder($child);
            }

            $this->buildQuery($child);
        }
    }

	/**
	 * @param Property $property
	 */
	private function buildJoin(Property $property)
    {
        $parentAlias = $this->alias($property->getParent()) . '.' . $property->getName();
        $alias = $this->alias($property);

        if (!isset($this->joinMemo[$alias])) {
            $this->qb->leftJoin($parentAlias, $alias);
            $this->joinMemo[$alias] = true;
        }
    }

	/**
	 * @param Property $property
	 */
	private function buildWhere(Property $property)
    {
        $value = $property->getField()->getValue();

        if (!$value || (is_array($value) && !count(array_filter($value)))) {
            return;
        }

        $alias = $this->alias($property->getParent()) . '.' . $property->getName();

//        if (!isset($this->whereMemo[$alias])) {
            $property->getField()->getWidget()->resolveCondition($this->qb, $alias, $value);
//            $this->whereMemo[$alias];
//        }
    }

	/**
	 * @param Property $property
	 */
	private function buildOrder(Property $property)
    {
        $alias = $this->alias($property->getParent()) . '.' . $property->getName();
        $order = $property->getOrder();

        $this->orders[$order['index']] = array(
            'alias' => $alias,
            'type' => $order['type'],
        );
    }

	/**
	 *
	 */
	private function buildOrdersArray()
    {
        ksort($this->orders);

        foreach ($this->orders as $order) {
            $this->qb->addOrderBy($order['alias'], $order['type']);
        }
    }

	/**
	 * @param Property $root
	 * @param $frags
	 */
	private function appendQueryFragments(Property $root, $frags)
    {
        $_this = $this;

        $magic = function($path) use ($root, $_this) {
            $property = $root->insert($path);

            if (!$property) {
                throw new \Exception("{$path} was not found in the query fragment.");
            }

            $parentAlias = $_this->alias($property->getParent());
            $alias = sprintf('%s.%s', $parentAlias, $property->getName());

            return $alias;
        };

        foreach ($frags as $frag) {
            $frag($this->qb, $magic, false);
        }

        if ($this->filter->getExecutedAction()->getWrappedAction()) {
            $this->filter
                ->getExecutedAction()
                ->getWrappedAction()
                ->prepare($this->qb, $magic, $root);
        }

        $this->buildQuery($root);
    }

	/**
	 * @param Property $property
	 * @return string
	 */
	private function alias(Property $property)
    {
        return sprintf('_%s', str_replace('.', '_', $property->getFullPath()));
    }
}
