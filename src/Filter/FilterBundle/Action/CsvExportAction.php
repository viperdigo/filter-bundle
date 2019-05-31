<?php

namespace Filter\FilterBundle\Action;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Filter\FilterBundle\Service\Filter\Property;
use Filter\FilterBundle\Service\Filter\Action;

/**
 * Class CsvExportAction
 * @package Filter\FilterBundle\Action
 */
class CsvExportAction implements Action
{
	/**
	 * @var array
	 */
	private $fields;

	/**
	 * CsvExportAction constructor.
	 * @param array $fields
	 */
	public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

	/**
	 * @param QueryBuilder $qb
	 * @param callable $alias
	 * @param Property $root
	 */
	public function prepare(QueryBuilder $qb, callable $alias, Property $root)
    {
        $fields = array_map(
            function ($value) use ($alias) {
                return $alias($value);
            },
            $this->fields
        );

        $qb->select($fields);

        $identifier = $root->getMetadata()->getClassMetadata()->getIdentifier();
        foreach ($identifier as $pk) {
            $qb->addGroupBy($alias($pk));
        }
    }

	/**
	 * @param Query $query
	 * @throws \Exception
	 */
	public function execute(Query $query)
    {
        $stm = $this->buildStatement($query);

        if ($stm->rowCount() <= 0) {
            throw new \Exception(sprintf('No rows (%s) to export.', $stm->rowCount()));
        }

        $filename = $this->createFile($stm);

        $this->output($filename);
    }

	/**
	 * @param Query $query
	 * @return \Doctrine\DBAL\Driver\Statement
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function buildStatement(Query $query)
    {
        $sql = $query->getSQL();
        $parameters = $query->getParameters();

        $parser = new \Doctrine\ORM\Query\Parser($query);
        $result = $parser->parse();
        $paramMappings = $result->getParameterMappings();

        $params = array();
        $types = array();

        foreach ($parameters as $parameter) {
            $mappings = $paramMappings[$parameter->getName()];

            foreach ($mappings as $position) {
                $params[$position] = $parameter->getValue();
                $types[$position] = $parameter->getType();
            }
        }

        return $query->getEntityManager()->getConnection()->executeQuery($sql, $params, $types);
    }

	/**
	 * @param $stm
	 * @return string
	 */
	private function createFile($stm)
    {
        $filename = sprintf('%s/exported_%s.csv', sys_get_temp_dir(), date('YmdHis'));

        $file = fopen($filename, 'w');

        $data = $stm->fetch();
        $headers = array_keys($data);

        fputcsv($file, $headers, ';', '"');
        fputcsv($file, $data, ';', '"');

        while ($data = $stm->fetch()) {
            fputcsv($file, $data, ';', '"');
        }

        fclose($file);

        return $filename;
    }

	/**
	 * @param $filename
	 */
	private function output($filename)
    {
        header('Content-type: text/csv');
        header('Content-disposition: ' . sprintf('attachment; filename="%s"', basename($filename)));
        header('Content-length: ' . filesize($filename));
        readfile($filename);
        exit;
    }
}
