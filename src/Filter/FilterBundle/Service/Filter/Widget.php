<?php

namespace Filter\FilterBundle\Service\Filter;

/**
 * Interface Widget
 * @package Filter\FilterBundle\Service\Filter
 */
interface Widget
{
	/**
	 * @param $value
	 * @return mixed
	 */
	public function isActive($value);

	/**
	 * @param $label
	 * @param $name
	 * @param $value
	 * @param $data
	 * @param $length
	 * @return mixed
	 */
	public function renderField($label, $name, $value, $data, $length);

	/**
	 * @param $label
	 * @param $name
	 * @param $value
	 * @param $data
	 * @return mixed
	 */
	public function renderCaption($label, $name, $value, $data);

	/**
	 * @param $qb
	 * @param $alias
	 * @param $value
	 * @return mixed
	 */
	public function resolveCondition($qb, $alias, $value);
}
