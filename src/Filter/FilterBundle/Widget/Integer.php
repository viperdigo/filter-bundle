<?php

namespace Filter\FilterBundle\Widget;

use Filter\FilterBundle\Service\Filter\Widget;

/**
 * Class Integer
 * @package Filter\FilterBundle\Widget
 */
class Integer implements Widget
{
	/**
	 * @var
	 */
	private $twig;

	/**
	 * Integer constructor.
	 * @param $twig
	 */
	public function __construct($twig)
    {
        $this->twig = $twig;
    }

	/**
	 * @param $value
	 * @return int|mixed
	 */
	public function isActive($value)
    {
		return is_array($value) ? count($value) : 0;
    }

	/**
	 * @param $label
	 * @param $name
	 * @param $value
	 * @param $data
	 * @param $length
	 * @return mixed
	 */
	public function renderField($label, $name, $value, $data, $length)
    {
        return $this->twig->render(
            'FilterBundle:Widget:integer.html.twig',
            array(
                'label' => $label,
                'name' => $name,
                'value' => $value,
                'length' => $length,
                'active' => $this->isActive($value),
            )
        );
    }

	/**
	 * @param $label
	 * @param $name
	 * @param $value
	 * @param $data
	 * @return mixed
	 */
	public function renderCaption($label, $name, $value, $data)
    {
        return $this->twig->render(
            'FilterBundle:Widget:integer_caption.html.twig',
            array(
                'label' => $label,
                'value' => $value,
            )
        );
    }

	/**
	 * @param $qb
	 * @param $alias
	 * @param $value
	 * @return mixed|void
	 */
	public function resolveCondition($qb, $alias, $value)
    {
        $placeholder = 'i_' . str_replace('.', '_', $alias);

        $values = preg_split('/[^0-9]/', $value);

        $qb->andWhere(sprintf('%s IN (:%s)', $alias, $placeholder));
        $qb->setParameter($placeholder, $values);
    }

	/**
	 * @return string
	 */
	public function getName()
    {
        return 'integer';
    }
}
