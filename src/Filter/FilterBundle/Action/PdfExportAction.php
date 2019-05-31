<?php

namespace Filter\FilterBundle\Action;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Filter\FilterBundle\Service\Filter\Property;
use Filter\FilterBundle\Service\Filter\Action;
use Knp\Snappy\Pdf;

/**
 * Class PdfExportAction
 * @package Filter\FilterBundle\Action
 */
class PdfExportAction implements Action
{
	/**
	 * @var array
	 */
	private $fields;

	/**
	 * @var \Twig_Template
	 */
	private $template;

	/**
	 * @var Pdf
	 */
	private $pdf;

	/**
	 * @var \Twig_Environment
	 */
	private $twig;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @var  array
	 */
	private $optionsPdf;

	/**
	 * PdfExportAction constructor.
	 * @param Pdf $pdf
	 * @param \Twig_Environment $twig
	 * @param $template
	 * @param array $fields
	 * @param array $options
	 * @param array $optionsPdf
	 */
	public function __construct(Pdf $pdf,
								\Twig_Environment $twig,
								$template,
								array $fields,
								array $options = array(),
								array $optionsPdf = array())
	{
		$this->template = $template;
		$this->fields = $fields;
		$this->pdf = $pdf;
		$this->twig = $twig;
		$this->options = $options;
		$this->optionsPdf = $optionsPdf;
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
		$result = $query->getResult();
		$count = count($result);
		if ($count <= 0) {
			throw new \Exception(sprintf('No rows (%s) to export.', $count));
		}

		$pdf = $this->generatePdf($result);
		$this->output($pdf);
	}

	/**
	 * @param $stm
	 * @return string
	 * @throws \Twig_Error_Loader
	 * @throws \Twig_Error_Runtime
	 * @throws \Twig_Error_Syntax
	 */
	private function generatePdf($stm)
	{

		$html = $this->twig->render($this->template, array('data' => $stm));

		return $this->pdf->getOutputFromHtml($html, $this->optionsPdf);

	}

	/**
	 * @param $output
	 * @throws \Exception
	 */
	private function output($output)
	{
		header('Content-type: application/pdf');

		if ($this->options) {

			$now = new \DateTime('now');
			if (key_exists('filename', $this->options)) {
				$filename = $this->options['filename'] . '_' . $now->format('YmdHis') . '.pdf';
			} else {
				$filename = 'pdf_' . $now->format('YmdHis') . '.pdf';
			}

			if (key_exists('downloadable', $this->options)) {
				header('Content-disposition: ' . sprintf('attachment; filename="%s"', basename($filename)));
			}
		}

		header('Content-length: ' . strlen($output));
		echo $output;
	}
}
