<?php

namespace Filter\FilterBundle\Service\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Class Metadata
 * @package Filter\FilterBundle\Service\Filter
 */
class Metadata {
	/**
	 *
	 */
	const TYPE_BOOLEAN = 'boolean';
	/**
	 *
	 */
	const TYPE_STRING = 'string';
	/**
	 *
	 */
	const TYPE_TEXT = 'text';
	/**
	 *
	 */
	const TYPE_DECIMAL = 'decimal';
	/**
	 *
	 */
	const TYPE_INTEGER = 'integer';
	/**
	 *
	 */
	const TYPE_BIGINT = 'bigint';
	/**
	 *
	 */
	const TYPE_DATETIME = 'datetime';
	/**
	 *
	 */
	const TYPE_DATE = 'date';
	/**
	 *
	 */
	const TYPE_TIME = 'time';
	/**
	 *
	 */
	const TYPE_ENUM = 'enum';
	/**
	 *
	 */
	const TYPE_MANY_TO_ONE = 'many_to_one';
	/**
	 *
	 */
	const TYPE_ONE_TO_MANY = 'one_to_many';
	/**
	 *
	 */
	const TYPE_ONE_TO_ONE = 'one_to_one';

	/**
	 * @var
	 */
	private $type;
	/**
	 * @var
	 */
	private $columnDefinition;
	/**
	 * @var
	 */
	private $classMetadata;

	/**
	 * Metadata constructor.
	 * @param $type
	 * @param $columnDefinition
	 * @param $classMetadata
	 */
	public function __construct($type, $columnDefinition, $classMetadata ) {
		$this->type             = $type;
		$this->columnDefinition = $columnDefinition;
		$this->classMetadata    = $classMetadata;
	}

	/**
	 * @return mixed
	 */
	public function getClass() {
		return $this->classMetadata->getName();
	}

	/**
	 * @param $classMetadata
	 */
	public function setClassMetadata($classMetadata ) {
		$this->classMetadata = $classMetadata;
	}

	/**
	 * @return mixed
	 */
	public function getClassMetadata() {
		return $this->classMetadata;
	}

	/**
	 * @param $type
	 */
	public function setType($type ) {
		$this->type = $type;
	}

	/**
	 * @return mixed
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param $columnDefinition
	 */
	public function setColumnDefinition($columnDefinition ) {
		$this->columnDefinition = $columnDefinition;
	}

	/**
	 * @return mixed
	 */
	public function getColumnDefinition() {
		return $this->columnDefinition;
	}

	/**
	 * @param Property $property
	 * @param EntityManager $em
	 * @return Metadata
	 * @throws \Doctrine\ORM\Mapping\MappingException
	 */
	public static function createMetadataFromProperty(Property $property, EntityManager $em ) {
		$propertyName     = $property->getName();
		$parentClass      = $property->getParent()->getMetadata()->getClass();
		$metadata         = $em->getClassMetadata( $parentClass );
		$fieldNames       = $metadata->getFieldNames();
		$associationNames = $metadata->getAssociationNames();

		if ( in_array( $property->getName(), $fieldNames ) ) {
			$mapping          = $metadata->getFieldMapping( $propertyName );
			$columnDefinition = isset( $mapping['columnDefinition'] ) ? $mapping['columnDefinition'] : null;
			$type             = self::translateType( $mapping['type'], $columnDefinition );
			$classMetadata    = null;

			return new self( $type, $columnDefinition, $classMetadata );
		}

		if ( in_array( $property->getName(), $associationNames ) ) {
			$mapping          = $metadata->getAssociationMapping( $propertyName );
			$columnDefinition = null;
			$type             = self::translateType( $mapping['type'], $columnDefinition );
			$classMetadata    = $em->getClassMetadata( $mapping['targetEntity'] );

			return new self( $type, $columnDefinition, $classMetadata );
		}

		throw new \Exception( "Class {$parentClass} has no property {$propertyName}" );
	}

	/**
	 * @param $type
	 * @param $columnDefinition
	 * @return string
	 * @throws \Exception
	 */
	private static function translateType($type, $columnDefinition ) {
		switch ( $type ) {
			case 'string':
				if ( substr( $columnDefinition, 0, 4 ) === 'ENUM' ) {
					return self::TYPE_ENUM;
				}

				return self::TYPE_STRING;
				break;

			case 'text':
				return self::TYPE_TEXT;

			case 'integer':
				return self::TYPE_INTEGER;

			case 'bigint':
				return self::TYPE_BIGINT;

			case 'decimal':
				return self::TYPE_DECIMAL;

			case 'boolean':
				return self::TYPE_BOOLEAN;

			case 'datetime':
				return self::TYPE_DATETIME;

			case 'date':
				return self::TYPE_DATE;

			case 'time':
				return self::TYPE_TIME;

			case ClassMetadataInfo::MANY_TO_ONE:
				return self::TYPE_MANY_TO_ONE;

			case ClassMetadataInfo::ONE_TO_MANY:
				return self::TYPE_ONE_TO_MANY;

			case ClassMetadataInfo::ONE_TO_ONE:
				return self::TYPE_ONE_TO_ONE;

			default:
				throw new \Exception( "Type {$type} and column definition {$columnDefinition} could not be translated to Metadata types" );
		}
	}
}
