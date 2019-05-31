<?php

namespace Filter\FilterBundle\Service\Filter;

/**
 * Interface CountableAction
 * @package Filter\FilterBundle\Service\Filter
 */
interface CountableAction extends Action
{
	/**
	 * @return mixed
	 */
	public function count();
}
