<?php

namespace Filter\FilterBundle\Service\Filter;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

/**
 * Class ActionWrapper
 * @package Filter\FilterBundle\Service\Filter
 */
class ActionWrapper
{
	/**
	 * @var Action
	 */
	private $wrappedAction;
	/**
	 * @var array
	 */
	private $properties;
	/**
	 * @var
	 */
	private $roles;

	/**
	 * ActionWrapper constructor.
	 * @param Action $wrappedAction
	 * @param $properties
	 * @param $roles
	 */
	public function __construct(Action $wrappedAction, $properties, $roles)
    {
        $this->wrappedAction = $wrappedAction;
        $this->roles = $roles;

        if (is_array($properties)) {
            $this->properties = $properties;
        } else {
            $this->properties['name'] = $properties;
        }

    }

	/**
	 * @return array
	 */
	public function getProperties()
    {
        return $this->properties;
    }

	/**
	 * @return mixed
	 */
	public function getRoles()
    {
        return $this->roles;
    }

	/**
	 * @return Action
	 */
	public function getWrappedAction()
    {
        return $this->wrappedAction;
    }
}
