<?php

namespace Filter\FilterBundle\Service\Filter;

use Doctrine\ORM\EntityManager;
use Filter\FilterBundle\Widget\Select;

/**
 * Class Field
 * @package Filter\FilterBundle\Service\Filter
 */
class Field
{
	/**
	 * @var
	 */
	private $label;
	/**
	 * @var Widget
	 */
	private $widget;
	/**
	 * @var
	 */
	private $value;
	/**
	 * @var
	 */
	private $data;
	/**
	 * @var
	 */
	private $path;
	/**
	 * @var
	 */
	private $length;

	/**
	 * Field constructor.
	 * @param $path
	 * @param $label
	 * @param $value
	 * @param Widget $widget
	 * @param $data
	 * @param $length
	 */
	public function __construct($path, $label, $value, Widget $widget, $data, $length)
    {
        $this->path = $path;
        $this->label = $label;
        $this->value = $value;
        $this->widget = $widget;
        $this->data = $data;
        $this->length = $length;
    }

	/**
	 * @return mixed
	 */
	public function getPath()
    {
        return $this->path;
    }

	/**
	 * @return mixed
	 */
	public function getLabel()
    {
        return $this->label;
    }

	/**
	 * @return mixed
	 */
	public function getValue()
    {
        return $this->value;
    }

	/**
	 * @return Widget
	 */
	public function getWidget()
    {
        return $this->widget;
    }

	/**
	 * @return mixed
	 */
	public function getLength()
    {
        return $this->length;
    }

	/**
	 * @return mixed
	 */
	public function getData()
    {
        return $this->data;
    }

	/**
	 * @param Property $property
	 * @param $value
	 * @param WidgetCollection $widgets
	 * @param array $options
	 * @param EntityManager $em
	 * @param $filtered
	 * @return Field
	 * @throws \Exception
	 */
	public static function createFieldFromProperty(Property $property, $value, WidgetCollection $widgets, array $options, EntityManager $em, $filtered)
    {
        $type = $property->getMetadata()->getType();

        $defaults = array(
            'widget' => self::getDefaultWidgetFor($type),
            'label' => $property->getRoot()->getMetadata()->getClass() . '.' . $property->getPath(),
            'value' => null,
            'length'=> 2
        );

        if (!isset($options['data'])) {
            $defaults['data'] = self::getDefaultDataFor($type, $property, $em);
        }

        $options = array_merge($defaults, $options);

        $path = $property->getPath();
        $label = $options['label'];
        $widget = $widgets->get($options['widget']);
        $data = $options['data'];
        $length = $options['length'];
        $value = (is_null($value) && !$filtered) ? $options['value'] : $value ;

        return new self($path, $label, $value, $widget, $data, $length);
    }

	/**
	 * @param $type
	 * @return string
	 * @throws \Exception
	 */
	private static function getDefaultWidgetFor($type)
    {
        switch ($type) {
            case Metadata::TYPE_STRING:
                return 'textfield';

            case Metadata::TYPE_ENUM:
                return 'multiselect';

            case Metadata::TYPE_INTEGER:
                return 'integer';

            case Metadata::TYPE_BIGINT:
                return 'integer';

            case Metadata::TYPE_BOOLEAN:
                return 'select';

            case Metadata::TYPE_DATETIME:
                return 'daterange';
                return 'datetimerange';

            case Metadata::TYPE_DATE:
                return 'daterange';

            case Metadata::TYPE_TIME:
                return 'timerange';

            case Metadata::TYPE_MANY_TO_ONE:
                return 'multiselect';

            case Metadata::TYPE_ONE_TO_MANY:
                return 'multiselect';

            default:
                throw new \Exception("Type {$type} has no default widget.");
        }
    }

	/**
	 * @param $type
	 * @param Property $property
	 * @param EntityManager $em
	 * @return array|null
	 * @throws \Exception
	 */
	private static function getDefaultDataFor($type, Property $property, EntityManager $em)
    {
        switch ($type) {
            case Metadata::TYPE_STRING:
            case Metadata::TYPE_INTEGER:
            case Metadata::TYPE_BIGINT:
            case Metadata::TYPE_DATETIME:
            case Metadata::TYPE_DATE:
            case Metadata::TYPE_TIME:
                return null;

            case Metadata::TYPE_BOOLEAN:
                return array(
                    '' => '',
                    Select::VALUE_TRUE => Select::translate(Select::VALUE_TRUE),
                    Select::VALUE_FALSE => Select::translate(Select::VALUE_FALSE),
                );

            case Metadata::TYPE_ENUM:
                $columnDefinition = $property->getMetadata()->getColumnDefinition();
                $array = str_replace('ENUM', 'array', $columnDefinition);
                $array = eval(sprintf('return %s;', $array));

                return array_combine($array, $array);

            case Metadata::TYPE_MANY_TO_ONE:
            case Metadata::TYPE_ONE_TO_MANY:
                $identifier = $property->getMetadata()->getClassMetadata()->getIdentifier();

                if (count($identifier) > 1) {
                    throw new \Exception("Class {$property->getMetadata()->getClass()} has a composite primary key. Default data for MANY_TO_ONE does NOT support it.");
                }

                $getId = sprintf('get%s', ucfirst($identifier[0]));

                $cacheKey = $property->getMetadata()->getClass();

                $q = $em->createQueryBuilder()
                    ->select('e')
                    ->from($property->getMetadata()->getClass(), 'e')
                    ->getQuery()
                    ->useResultCache(true, 60 * 5, sprintf('filter-all-%s', $cacheKey)) // 5 minutes
                    ;

                $result = $q->getResult();
                $data = array();

                foreach ($result as $entity) {
                    $id = call_user_func(array($entity, $getId));
                    $data[$id] = (string) $entity;
                }

                return $data;

            default:
                throw new \Exception("Type {$type} has no default data.");
        }
    }
}
