<?php

namespace Filter\FilterBundle\Service\Filter;

/**
 * Class WidgetCollection
 * @package Filter\FilterBundle\Service\Filter
 */
class WidgetCollection
{
	/**
	 * @var array
	 */
	private $widgets = array();

	/**
	 * @param Widget $widget
	 */
	public function add(Widget $widget)
    {
        $this->widgets[$widget->getName()] = $widget;
    }

	/**
	 * @param $name
	 * @return mixed
	 * @throws \Exception
	 */
	public function get($name)
    {
        if (!isset($this->widgets[$name])) {
            throw new \Exception("Widget {$name} does not exist.");
        }

        return $this->widgets[$name];
    }

	/**
	 * @return array
	 */
	public function getAll()
    {
        return $this->widgets;
    }
}
