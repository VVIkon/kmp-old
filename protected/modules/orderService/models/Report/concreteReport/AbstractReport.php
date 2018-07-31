<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 13.09.17
 * Time: 17:56
 */
abstract class AbstractReport
{
    const NA = 'N/A';

    /**
     * @var SOReport
     */
    protected $SOReport;

    /**
     * @var OrdersServices[]|OrderModel[]
     */
    protected $data;


    /**
     *
     * @return mixed
     */
    abstract public function makeReport();

    abstract protected function init($params);

    abstract public function getEmailSubject();

    /**
     * @param $reportConstructType
     * @param $dateFrom
     * @param $dateTo
     * @param $companyId
     */
    abstract protected function extractData($reportConstructType, $dateFrom, $dateTo, $companyIds);

    public function __construct()
    {
        $this->SOReport = new SOReport();
    }

    public function getSOReport()
    {
        return $this->SOReport;
    }

    /**
     * Создание класса отчета
     * @param $reportName
     * @param $params
     * @return AbstractReport
     */
    public static function createReportClass($reportName, $params)
    {
        $className = ucfirst($reportName) . 'Report';

        if (class_exists($className)) {
            $class = new $className();
            $class->init($params);
            $class->extractData(
                $params['reportConstructType'],
                $params['dateFrom'],
                $params['dateTo'],
                StdLib::nvl($params['holdingCompaniesIds'])
            );
            return $class;
        }
    }

    /**
     * Создание пустой строки заданной длины с началом и/или концом из массива
     * @param $length
     * @param array $head
     * @param array $tail
     * @return array
     */
    protected function createEmptyRow($length, $head = [], $tail = [])
    {
        if (count($head) + count($tail) > $length) {
            return [];
        }

        $row = array_fill(0, $length, '');

        if (!empty($head)) {
            array_splice($row, 0, count($head), $head);
        }
        if (!empty($tail)) {
            array_splice($row, count($row) - count($tail), count($tail), $tail);
        }

        return $row;
    }
}