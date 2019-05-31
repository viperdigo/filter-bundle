<?php

namespace Filter\FilterBundle\Widget;

use Filter\FilterBundle\Service\Filter\Widget;

/**
 * Class DateRange
 * @package Filter\FilterBundle\Widget
 */
class DateRange implements Widget
{
	/**
	 * @var
	 */
	private $twig;

	/**
	 * DateRange constructor.
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
            'FilterBundle:Widget:dateRange.html.twig',
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
            'FilterBundle:Widget:dateRange_caption.html.twig',
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
	 * @throws \Exception
	 */
	public function resolveCondition($qb, $alias, $value)
    {
        $placeholder = function ($s) use ($alias) {
            return 'dr_'.$s.'_'.str_replace('.', '_', $alias);
        };

        if (isset($value['from']) && $value['from']) {
            $from = $value['from'];
            $date = new \DateTime();
            $from = $date->createFromFormat('d/m/Y', $from)->format('Y-m-d');

            $qb->andWhere(sprintf('DATE(%s) >= :%s', $alias, $placeholder('from')));
            $qb->setParameter($placeholder('from'), $from);
        }

        if (isset($value['to']) && $value['to']) {
            $to = $value['to'];
            $date = new \DateTime();
            $to = $date->createFromFormat('d/m/Y', $to)->format('Y-m-d');

            $qb->andWhere(sprintf('DATE(%s) <= :%s', $alias, $placeholder('to')));
            $qb->setParameter($placeholder('to'), $to);
        }
    }

	/**
	 * @return string
	 */
	public function getName()
    {
        return 'daterange';
    }
}
