<?php

namespace Filter\FilterBundle\Widget;

use Filter\FilterBundle\Service\Filter\Widget;

/**
 * Class Select
 * @package Filter\FilterBundle\Widget
 */
class Select implements Widget
{
	/**
	 *
	 */
	const VALUE_TRUE = 'true';
	/**
	 *
	 */
	const VALUE_FALSE = 'false';

	/**
	 * @var
	 */
	private $twig;

	/**
	 * Select constructor.
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
            'FilterBundle:Widget:select.html.twig',
            array(
                'label' => $label,
                'name' => $name,
                'value' => $value,
                'data' => $data,
                'length'=> $length,
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
	 * @throws \Exception
	 */
	public function renderCaption($label, $name, $value, $data)
    {
        return $this->twig->render(
            'FilterBundle:Widget:select_caption.html.twig',
            array(
                'label' => $label,
                'value' => self::translate($value),
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
        $placeholder = 'ss_'.str_replace('.', '_', $alias);

        $qb->andWhere(sprintf('%s = :%s', $alias, $placeholder));
        $qb->setParameter($placeholder, $value === self::VALUE_TRUE ? true : false);
    }

	/**
	 * @return string
	 */
	public function getName()
    {
        return 'select';
    }

	/**
	 * @param $value
	 * @return string
	 * @throws \Exception
	 */
	public static function translate($value)
    {
        switch ($value) {
            case self::VALUE_TRUE:
                return 'sim';
            case self::VALUE_FALSE:
                return 'nao';
            default:
                throw new \Exception("Value {$value} could not be translated by the Select widget.");
        }
    }
}
