<?php

namespace Filter\FilterBundle\Service\Filter;

/**
 * Class Filter
 * @package Filter\FilterBundle\Service\Filter
 */
class Filter
{
	/**
	 * @var
	 */
	private $fields;
	/**
	 * @var
	 */
	private $result;
	/**
	 * @var
	 */
	private $count;
	/**
	 * @var
	 */
	private $filtered;
	/**
	 * @var
	 */
	private $exported;
	/**
	 * @var
	 */
	private $executedAction;
	/**
	 * @var
	 */
	private $actions;
	/**
	 * @var
	 */
	private $exportable;
	/**
	 * @var
	 */
	private $selectedExportFields;
	/**
	 * @var
	 */
	private $action;

	/**
	 * @param $selectedExportFields
	 */
	public function setSelectedExportFields($selectedExportFields)
    {
        $this->selectedExportFields = $selectedExportFields;
    }

	/**
	 * @return mixed
	 */
	public function getSelectedExportFields()
    {
        return $this->selectedExportFields;
    }

	/**
	 * @param $result
	 */
	public function setResult($result)
    {
        $this->result = $result;
    }

	/**
	 * @return mixed
	 */
	public function getResult()
    {
        return $this->result;
    }

	/**
	 * @param $count
	 */
	public function setCount($count)
    {
        $this->count = $count;
    }

	/**
	 * @return mixed
	 */
	public function getCount()
    {
        return $this->count;
    }

	/**
	 * @param Field $field
	 */
	public function addField(Field $field)
    {
        $this->fields[] = $field;
    }

	/**
	 * @param $fields
	 */
	public function setFields($fields)
    {
        $this->fields = $fields;
    }

	/**
	 * @return mixed
	 */
	public function getFields()
    {
        return $this->fields;
    }

	/**
	 * @param $filtered
	 */
	public function setFiltered($filtered)
    {
        $this->filtered = $filtered;
    }

	/**
	 * @return mixed
	 */
	public function isFiltered()
    {
        return $this->filtered;
    }

	/**
	 * @param $exported
	 */
	public function setExported($exported)
    {
        $this->exported = $exported;
    }

	/**
	 * @return mixed
	 */
	public function isExported()
    {
        return $this->exported;
    }

	/**
	 * @param ActionWrapper $executedAction
	 */
	public function setExecutedAction(ActionWrapper $executedAction)
    {
        $this->executedAction = $executedAction;
    }

	/**
	 * @return mixed
	 */
	public function getExecutedAction()
    {
        return $this->executedAction;
    }

	/**
	 * @param $exportable
	 */
	public function setExportable($exportable)
    {
        $this->exportable = $exportable;
    }

	/**
	 * @return mixed
	 */
	public function isExportable()
    {
        return $this->exportable;
    }

	/**
	 * @param ActionWrapper $action
	 */
	public function addAction(ActionWrapper $action)
    {
        $this->actions[$action->getProperties()['name']] = $action;
    }

	/**
	 * @param $actions
	 */
	public function setActions($actions)
    {
        $this->actions = $actions;
    }

	/**
	 * @return mixed
	 */
	public function getActions()
    {
        return $this->actions;
    }

	/**
	 * @param $name
	 * @return |null
	 */
	public function getAction($name)
    {
        return isset($this->actions[$name]) ? $this->actions[$name] : null ;
    }

	/**
	 * @return bool
	 */
	public function isReallyFiltered()
    {
        foreach ($this->fields as $field) {
            if ($field->getWidget()->isActive($field->getValue())) {
                return true;
            }
        }

        return false;
    }
}
