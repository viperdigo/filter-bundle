<?php

namespace Filter\FilterBundle\Twig;

use Filter\FilterBundle\Service\Filter\Filter;
use Filter\FilterBundle\Service\Filter\Manager;

/**
 * Class FilterExtension
 * @package Filter\FilterBundle\Twig
 */
class FilterExtension extends \Twig_Extension
{
	/**
	 * @var
	 */
	private $container;

	/**
	 * FilterExtension constructor.
	 * @param $container
	 */
	public function __construct($container)
    {
        $this->container = $container;
    }

	/**
	 * @return array
	 */
	public function getFunctions()
    {
        return array(
            'filter_render' => new \Twig_Function_Method(
                $this, 'filterRender', array(
                    'is_safe' => array('html'),
                )
            ),
            'filter_query_string' => new \Twig_Function_Method(
                $this, 'filterQueryString', array(
                    'is_safe' => array('html'),
                )
            ),
        );
    }

	/**
	 * @param Filter $filter
	 * @return mixed
	 */
	public function filterRender(Filter $filter)
    {
        return $this->container->get('twig')->render(
            'FilterBundle:Filter:render.html.twig',
            array(
                'filter' => $filter,
                'prefix' => Manager::PREFIX,
            )
        );
    }

	/**
	 * @param array $data
	 * @return string
	 */
	public function filterQueryString(array $data)
    {
        $filter = $this->container->get('filter');

        return http_build_query($filter->generateQueryString($data));
    }

	/**
	 * @return string
	 */
	public function getName()
    {
        return 'filter_extension';
    }
}
