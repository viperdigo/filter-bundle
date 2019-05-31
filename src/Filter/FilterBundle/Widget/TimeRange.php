<?php

namespace Filter\FilterBundle\Widget;

use Filter\FilterBundle\Service\Filter\Widget;

/**
 * Class TimeRange
 * @package Filter\FilterBundle\Widget
 */
class TimeRange implements Widget
{
	/**
	 * @var
	 */
	private $twig;

	/**
	 * TimeRange constructor.
	 * @param $twig
	 */
	public function __construct($twig)
    {
        $this->twig = $twig;
    }

	/**
	 * @param $value
	 * @return bool|mixed
	 */
	public function isActive($value)
    {
        return is_array($value) && ($value['from'] || $value['to']);
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
            'FilterBundle:Widget:timeRange.html.twig',
            array(
                'label' => $label,
                'name' => $name,
                'value' => $value,
                'data' => $data,
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
        $value = array_map(
            function ($value) {
                return $value ? $value : '...';
            },
            $value
        );

        return $this->twig->render(
            'FilterBundle:Widget:timeRange_caption.html.twig',
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
        $placeholder = function ($s) use ($alias) {
            return 'tr_'.$s.'_'.str_replace('.', '_', $alias);
        };

        if (isset($value['from']) && $value['from']) {
            $from = $value['from'];
            $qb->andWhere(sprintf('%s >= :%s', $alias, $placeholder('from')));
            $qb->setParameter($placeholder('from'), $from);
        }

        if (isset($value['to']) && $value['to']) {
            $to = $value['to'];
            $qb->andWhere(sprintf('%s <= :%s', $alias, $placeholder('to')));
            $qb->setParameter($placeholder('to'), $to);
        }
    }

	/**
	 * @return string
	 */
	public function getName()
    {
        return 'timerange';
    }
}
