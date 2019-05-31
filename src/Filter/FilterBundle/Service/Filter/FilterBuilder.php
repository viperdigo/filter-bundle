<?php

namespace Filter\FilterBundle\Service\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Filter\FilterBundle\Action\QueryAction;

/**
 * Class FilterBuilder
 * @package Filter\FilterBundle\Service\Filter
 */
class FilterBuilder
{
	/**
	 * @var Manager
	 */
	private $manager;
	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var
	 */
	private $root;
	/**
	 * @var array
	 */
	private $fields = array();
	/**
	 * @var array
	 */
	private $orders = array();
	/**
	 * @var array
	 */
	private $queryFragments = array();
	/**
	 * @var array
	 */
	private $eagerLoads = array();
	/**
	 * @var bool
	 */
	private $forcePartialLoad = false;
	/**
	 * @var array
	 */
	private $actions = array();
	/**
	 * @var
	 */
	private $resultAction;
	/**
	 * @var
	 */
	private $pagination;
	/**
	 * @var int
	 */
	private $cache = 60; // 1 minute
	/**
	 * @var
	 */
	private $selectedExportFields;

	/**
	 * FilterBuilder constructor.
	 * @param Manager $manager
	 * @param $root
	 * @param $em
	 * @throws \Exception
	 */
	public function __construct(Manager $manager, $root, $em)
    {
        $this->manager = $manager;
        $this->root = $root;

        switch ($em) {
            case 'default':
                $this->em = $this->manager->getDefaultEntityManager();
                break;

            default:
                throw new \Exception("Entity Manager {$em} does not exist.");
                break;
        }
    }

	/**
	 * @param $field
	 * @param array $data
	 * @return $this
	 */
	public function addField($field, array $data = array())
    {
        $this->fields[$field] = $data;

        return $this;
    }

	/**
	 * @param array $data
	 * @return $this
	 */
	public function selectExportFields($data = array())
    {
        $this->selectedExportFields = $data;

        return $this;
    }

	/**
	 * @param $field
	 * @return $this
	 */
	public function removeField($field)
    {
        unset($this->fields[$field]);

        return $this;
    }

	/**
	 * @param $field
	 * @param $type
	 * @return $this
	 */
	public function addOrder($field, $type)
    {
        $this->orders[$field] = array(
            'type' => $type,
            'index' => count($this->orders),
        );

        return $this;
    }

	/**
	 * @param $field
	 * @return $this
	 */
	public function removeOrder($field)
    {
        unset($this->orders[$field]);

        return $this;
    }

	/**
	 * @param $frag
	 * @return $this
	 */
	public function addQueryFragment($frag)
    {
    	if($frag)
        $this->queryFragments[] = $frag;

        return $this;
    }

	/**
	 * @param $propertyName
	 * @return $this
	 */
	public function addEagerLoad($propertyName)
    {
        $this->eagerLoads[] = $propertyName;

        return $this;
    }

	/**
	 * @param $pages
	 * @return $this
	 */
	public function addPagination($pages)
    {
        $this->pagination = $pages;

        return $this;
    }

	/**
	 * @param $properties
	 * @param Action $action
	 * @param array $roles
	 * @return $this
	 */
	public function addAction($properties, Action $action, $roles = array())
    {
        $this->actions[] = new ActionWrapper($action, $properties, $roles);

        return $this;
    }

	/**
	 * @param Action $action
	 * @return $this
	 */
	public function addResultAction(Action $action)
    {
        $this->resultAction = $action;

        return $this;
    }

	/**
	 * @param $seconds
	 * @return $this
	 */
	public function addCache($seconds)
    {
        $this->cache = $seconds;

        return $this;
    }

	/**
	 * @param bool $forcePartialLoad
	 * @return $this
	 */
	public function forcePartialLoad($forcePartialLoad = true)
    {
        $this->forcePartialLoad = $forcePartialLoad;

        return $this;
    }

	/**
	 * @return Filter
	 * @throws \Exception
	 */
	public function build()
    {
        $filter = new Filter();
        $request = $this->manager->getRequest();
        $class = $this->em->getClassMetadata($this->root)->getName();
        $executedAction = $request->get($this->prefix('action'));
        $filter->setFiltered(!!$request->get($this->prefix('filtered')) || !!$request->get($this->prefix('action')));
        $filter->setSelectedExportFields($this->selectExportFields());

        // Add all authorized actions to the filter.
        foreach ($this->actions as $action) {
            $roles = $action->getRoles();

            if (!count($roles)) {
                $filter->addAction($action);
            } else if ($this->manager->getAuthorizationChecker()->isGranted($roles)) {
                $filter->addAction($action);
            }
        }

        // Decide the executed action.
        if (is_array($executedAction)) {
            $executedAction = array_keys($executedAction);

            if (!isset($executedAction[0])) {
                throw new \Exception('No action found.');
            }

            $executedAction = $executedAction[0];
            $executedAction = $filter->getAction($executedAction);
        } else {
            if ($this->resultAction) {
                $executedAction = $this->resultAction;
            } else {
                $executedAction = new QueryAction(
                    $this->manager->getPaginator(),
                    $this->manager->getRequest(),
                    $this->pagination,
                    $this->forcePartialLoad
                );
            }

            $executedAction = new ActionWrapper($executedAction, 'result', array());
        }

        // Set the executed action.
        $filter->setExecutedAction($executedAction);

        // Get the wrapped action.
        $wrappedAction = $filter->getExecutedAction()->getWrappedAction();

        // Merge all the properties explicitly chosen by the user.
        $properties = array_keys(array_merge($this->fields, $this->orders));
        $properties = array_merge($properties, $this->eagerLoads);

        // Build the property tree.
        $tree = $this->buildTree($class, $properties);
        $this->buildMetadata($tree);
        $this->buildFields($tree, $filter);
        $this->buildOrders($tree);
        $this->buildEagerLoads($tree);

        // Build the query.
        $qb = new QueryBuilder($this->em);
        $qb->build($tree, $filter, $this->queryFragments);
        $query = $qb->getQueryBuilder()->getQuery();

        // Build the cache key and use the cache.
        if ($this->cache) {
            $cacheKey = $this->buildCacheKey($query);
            $query->useResultCache(true, $this->cache, $cacheKey);
        }

        // Actually execute the action.
        $result = $wrappedAction->execute($query);

        // Set the result.
        $filter->setResult($result);

        // If the action is countable, do count it.
        if ($wrappedAction instanceof CountableAction) {
            $filter->setCount($wrappedAction->count());
        }

        // Add this filter to the manager, so we keep track
        // of what filters exist on this page.
        $this->manager->addFilter($filter);

        // Always return the filter.
        return $filter;
    }

	/**
	 * @param $class
	 * @param $paths
	 * @return Property|string
	 */
	private function buildTree($class, $paths)
    {
        $metadata = new Metadata(null, null, $this->em->getClassMetadata($class));
        $root = lcfirst(trim(strrchr($class, '\\'), '\\'));
        $root = new Property($root);
        $root->setMetadata($metadata);

        foreach ($paths as $path) {
            $root->insert($path);
        }

        return $root;
    }

	/**
	 * @param Property $property
	 * @throws \Exception
	 */
	private function buildMetadata(Property $property)
    {
        $children = $property->getChildren();

        foreach ($children as $child) {
            $metadata = Metadata::createMetadataFromProperty($child, $this->em);
            $child->setMetadata($metadata);
            $this->buildMetadata($child);
        }
    }

	/**
	 * @param Property $property
	 * @param Filter $filter
	 * @throws \Exception
	 */
	private function buildFields(Property $property, Filter $filter)
    {
        $children = $property->getChildren();

        foreach ($children as $child) {
            $path = $child->getPath();

            if (isset($this->fields[$path])) {
                $value = $this->processValue($path);
                $options = $this->fields[$path];
                $field = Field::createFieldFromProperty($child, $value, $this->manager->getWidgets(), $options, $this->em, $filter->isFiltered());
                $child->setField($field);
                $filter->addField($field);
            }

            $this->buildFields($child, $filter);
        }

    }

	/**
	 * @param Property $property
	 */
	private function buildOrders(Property $property)
    {
        $children = $property->getChildren();

        foreach ($children as $child) {
            $path = $child->getPath();

            if (isset($this->orders[$path])) {
                $child->setOrder($this->orders[$path]);
            }

            $this->buildOrders($child);
        }
    }

	/**
	 * @param Property $property
	 */
	private function buildEagerLoads(Property $property)
    {
        $children = $property->getChildren();

        foreach ($children as $child) {
            $path = $child->getPath();

            if (in_array($path, $this->eagerLoads)) {
                $child->setEagerLoad(true);
            }

            $this->buildEagerLoads($child);
        }
    }

	/**
	 * @param $query
	 * @return array|string
	 */
	private function buildCacheKey($query)
    {
        $cacheKey = array($query->getDQL());

        foreach ($query->getParameters() as $parameter) {
            $cacheKey[] = sprintf(
                '%s=%s',
                $parameter->getName(),
                serialize($parameter->getValue())
            );
        }

        $cacheKey = implode('|', $cacheKey);
        $cacheKey = sprintf('filter-%s', sha1($cacheKey));

        return $cacheKey;
    }

	/**
	 * @param $s
	 * @return string
	 */
	private function prefix($s)
    {
        return $this->manager->prefix($s);
    }

	/**
	 * @param $path
	 * @return mixed
	 */
	private function processValue($path)
    {
        return $this->manager->getRequest()->get($this->prefix(str_replace('.', ':', $path)));
    }
}
