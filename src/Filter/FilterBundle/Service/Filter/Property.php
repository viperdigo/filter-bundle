<?php

namespace Filter\FilterBundle\Service\Filter;

use Doctrine\ORM\EntityManager;

/**
 * Class Property
 * @package Filter\FilterBundle\Service\Filter
 */
class Property
{
	/**
	 * @var
	 */
	private $name;
	/**
	 * @var null
	 */
	private $parent;
	/**
	 * @var array
	 */
	private $children = array();

	/**
	 * @var
	 */
	private $field;
	/**
	 * @var
	 */
	private $order;
	/**
	 * @var
	 */
	private $metadata;
	/**
	 * @var
	 */
	private $exportIndex;
	/**
	 * @var
	 */
	private $eagerLoad;

	/**
	 * Property constructor.
	 * @param $name
	 * @param null $parent
	 */
	public function __construct($name, $parent = null)
    {
        $this->name = $name;
        $this->parent = $parent;
    }

	/**
	 * @param $path
	 * @return mixed
	 */
	public function insert($path)
    {
        $names = explode('.', $path);
        $name = array_shift($names);

        if (!isset($this->children[$name])) {
            $this->children[$name] = new Property($name, $this);
        }

        if (count($names)) {
            return $this->children[$name]->insert(implode('.', $names));
        }

        return $this->children[$name];
    }

	/**
	 * @param $path
	 * @return bool|mixed
	 */
	public function find($path)
    {
        $names = explode('.', $path);
        $name = array_shift($names);

        if (isset($this->children[$name])) {
            if (count($names) === 0) {
                return $this->children[$name];
            }

            return $this->children[$name]->find(implode('.', $names));
        }

        return false;
    }

	/**
	 * @param array $data
	 * @return string
	 */
	public function getPath($data = array())
    {
        $path = $this->getFullPath();
        $names = explode('.', $path);
        $root = array_shift($names);
        return implode('.', $names);
    }

	/**
	 * @param array $data
	 * @return array|string
	 */
	public function getFullPath($data = array())
    {
        $implode = !count($data);

        $data[] = $this->getName();

        if ($this->parent) {
            $data = $this->parent->getFullPath($data);
        }

        return $implode ? implode('.', array_reverse($data)) : $data ;
    }

	/**
	 * @return mixed
	 */
	public function getName()
    {
        return $this->name;
    }

	/**
	 * @return null
	 */
	public function getParent()
    {
        return $this->parent;
    }

	/**
	 * @return array
	 */
	public function getChildren()
    {
        return $this->children;
    }

	/**
	 * @param Metadata $metadata
	 */
	public function setMetadata(Metadata $metadata)
    {
        $this->metadata =  $metadata;
    }

	/**
	 * @return mixed
	 */
	public function getMetadata()
    {
        return $this->metadata;
    }

	/**
	 * @param Field $field
	 */
	public function setField(Field $field)
    {
        $this->field =  $field;
    }

	/**
	 * @return mixed
	 */
	public function getField()
    {
        return $this->field;
    }

	/**
	 * @param $order
	 */
	public function setOrder($order)
    {
        $this->order =  $order;
    }

	/**
	 * @return mixed
	 */
	public function getOrder()
    {
        return $this->order;
    }

	/**
	 * @param $exportIndex
	 */
	public function setExportIndex($exportIndex)
    {
        $this->exportIndex =  $exportIndex;
    }

	/**
	 * @return mixed
	 */
	public function getExportIndex()
    {
        return $this->exportIndex;
    }

	/**
	 * @param $eagerLoad
	 */
	public function setEagerLoad($eagerLoad)
    {
        $this->eagerLoad = $eagerLoad;
    }

	/**
	 * @return mixed
	 */
	public function getEagerLoad()
    {
        return $this->eagerLoad;
    }

	/**
	 * @return $this
	 */
	public function getRoot()
    {
        if ($this->parent) {
            return $this->parent->getRoot();
        }

        return $this;
    }

	/**
	 *
	 */
	public function debug()
    {
        foreach ($this->children as $child) {
            printf("%s\n", implode('.', $child->getPath()));
            $child->debug();
        }
    }
}
