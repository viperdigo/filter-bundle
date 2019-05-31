<?php

namespace Filter\FilterBundle\Action;

use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Filter\FilterBundle\Service\Filter\Property;
use Filter\FilterBundle\Service\Filter\Action;
use Liuggio\ExcelBundle\Factory;

/**
 * Class ExcelExportAction
 * @package Filter\FilterBundle\Action
 */
class ExcelExportAction implements Action
{
	/**
	 * @var array
	 */
	private $fields;
	/**
	 * @var Factory
	 */
	private $factoryExcel;
	/**
	 * @var
	 */
	private $objExcel;
	/**
	 * @var
	 */
	private $title;
	/**
	 * @var array
	 */
	private $headers;
	/**
	 * @var null
	 */
	private $translate;

	/**
	 * ExcelExportAction constructor.
	 * @param Factory $factory
	 * @param $title
	 * @param array $fields
	 * @param array $headers
	 * @param null $translate
	 */
	public function __construct(Factory $factory, $title, array $fields, array $headers, $translate = null)
    {
        $this->fields = $fields;
        $this->factoryExcel = $factory;
        $this->title = $title;
        $this->headers = $headers;
        $this->translate = $translate;
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
	 * @param PDOStatement $stm
	 * @return string
	 */
	private function createFile(PDOStatement $stm)
    {
        $filename = sprintf('exported_%s.xls', date('YmdHis'));

        $headers = $this->headers;
        $this->objExcel = $this->factoryExcel->createPHPExcelObject();
        $activeSheet = $this->objExcel->setActiveSheetIndex(0);

        $numberLine = 1;
        $letterColumn = 65; # Letter A
        foreach ($headers as $column => $name) {
            $activeSheet->setCellValue(chr($letterColumn).$numberLine, $name);
            $letterColumn++;
        }

        $numberLine = 2;
        $letterColumn = 65;
        foreach ($stm->fetchAll() as $data) {

            foreach ($data as $key => $value) {
                if($this->translate){
                    $activeSheet->setCellValue(chr($letterColumn).$numberLine, $this->translate->trans($value));
                }else{
                    $activeSheet->setCellValue(chr($letterColumn).$numberLine, $value);
                }
                $letterColumn++;
            }

            $letterColumn = 65;
            $numberLine++;

        }

        $this->objExcel->getActiveSheet()->setTitle($this->title);

        return $filename;

    }

	/**
	 * @param $filename
	 */
	private function output($filename)
    {
        header('Content-Type: application/vnd.ms-excel');
        header(sprintf('Content-Disposition: attachment;filename="%s"', $filename));
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        $objWriter = $this->factoryExcel->createWriter($this->objExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

}
