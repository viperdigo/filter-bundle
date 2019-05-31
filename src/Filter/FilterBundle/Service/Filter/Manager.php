<?php

namespace Filter\FilterBundle\Service\Filter;

use Doctrine\ORM\EntityManager;
use Knp\Component\Pager\Paginator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Manager
 * @package Filter\FilterBundle\Service\Filter
 */
class Manager
{
	/**
	 *
	 */
	const PREFIX = 'filter';

	/**
	 * @var EntityManager
	 */
	private $emDefault;
	/**
	 * @var Request
	 */
	private $request;
	/**
	 * @var Paginator
	 */
	private $paginator;
	/**
	 * @var
	 */
	private $dispatcher;
	/**
	 * @var
	 */
	private $authorizationChecker;
	/**
	 * @var WidgetCollection
	 */
	private $widgets;
	/**
	 * @var array
	 */
	private $filters = array();

	/**
	 * Manager constructor.
	 * @param EntityManager $emDefault
	 * @param Request $request
	 * @param Paginator $paginator
	 * @param $dispatcher
	 * @param $authorizationChecker
	 * @param WidgetCollection $widgets
	 */
	public function __construct(EntityManager $emDefault, Request $request, Paginator $paginator, $dispatcher, $authorizationChecker, WidgetCollection $widgets)
    {
        $this->emDefault = $emDefault;
        $this->request = $request;
        $this->paginator = $paginator;
        $this->widgets = $widgets;
        $this->dispatcher = $dispatcher;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param $root
     * @param string $em
     * @return FilterBuilder
     */
    public function createFilterBuilder($root, $em = 'default')
    {
        return new FilterBuilder($this, $root, $em);

    }

	/**
	 * @param Filter $filter
	 */
	public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
    }

	/**
	 * @return array
	 */
	public function getFilters()
    {
        return $this->filters;
    }

	/**
	 * @return EntityManager
	 */
	public function getDefaultEntityManager()
    {
        return $this->emDefault;
    }

	/**
	 * @return Request
	 */
	public function getRequest()
    {
        return $this->request;
    }

	/**
	 * @return WidgetCollection
	 */
	public function getWidgets()
    {
        return $this->widgets;
    }

	/**
	 * @return Paginator
	 */
	public function getPaginator()
    {
        return $this->paginator;
    }

	/**
	 * @return mixed
	 */
	public function getDispatcher()
    {
        return $this->dispatcher;
    }

	/**
	 * @return mixed
	 */
	public function getAuthorizationChecker()
    {
        return $this->authorizationChecker;
    }

	/**
	 * @param $data
	 * @return array
	 */
	public function generateQueryString($data)
    {
        $qs = array(
            $this->prefix('filtered') => true,
        );

        foreach ($data as $k => $v) {
            $qs[$this->prefix(str_replace('.', ':', $k))] = $v;
        }

        return $qs;
    }

	/**
	 * @param $s
	 * @return string
	 */
	public function prefix($s)
    {
        return sprintf('%s:%s', self::PREFIX, $s);
    }
}
