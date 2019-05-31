<?php

namespace Filter\FilterBundle\Widget;

use Filter\FilterBundle\Service\Filter\Widget;

/**
 * Class Money
 * @package Filter\FilterBundle\Widget
 */
class Money implements Widget
{
	/**
	 * @var
	 */
	private $twig;

	/**
	 * Money constructor.
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
            'FilterBundle:Widget:money.html.twig',
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
            'FilterBundle:Widget:money_caption.html.twig',
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
        $placeholder = 'mo_' . str_replace('.', '_', $alias);
        $value = round($value * 100);

        $qb->andWhere(sprintf('%s = :%s', $alias, $placeholder));
        $qb->setParameter($placeholder, $value);
    }

	/**
	 * @return string
	 */
	public function getName()
    {
        return 'money';
    }
}
