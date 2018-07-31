<?php

/**
 * Пустой объект отчета
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 03.10.17
 * Time: 18:01
 */
class DummyReport extends AbstractReport
{
    private $emailSubject;

    protected function extractData($reportConstructType, $dateFrom, $dateTo, $companyId)
    {

    }

    public function getEmailSubject()
    {
        return $this->emailSubject;
    }

    /**
     * MSRReport constructor.
     * @param $params
     */
    public function init($params)
    {
        $this->emailSubject = $params['subject'];
        $reportData = StdLib::nvl($params['reportData']);
        $header = StdLib::nvl($reportData['mainHeader']);
//        // заголовок отчета
        $this->SOReport->setMainHeader($header);

        // Заполняем заголовок
        if(isset($reportData['headerTexts'])) {
            foreach ($reportData['headerTexts'] as $headerTexRow) {
                $this->SOReport->addHeaderTextRow([$headerTexRow[0], $headerTexRow[1]]);
            }
        }
        // Заполняем подвал
        if(isset($reportData['footerTexts'])) {
            foreach ($reportData['footerTexts'] as $footerTextRow) {
                $this->SOReport->addFooterTextRow([$footerTextRow[0], $footerTextRow[1]]);
            }
        }

        // таблица
        $table = $reportData['table'];
        // заголовок таблицы
        if(isset($table['headers'])){
            foreach ($table['headers'] as $header){
                $this->SOReport->addTableHeader($header);
            }
        }
        // тело
        if(isset($table['rowgroups'])){
            foreach ($table['rowgroups'] as $rowgroup){
                $soReportTableRowGroup = new SOReportTableRowGroup();
                $soReportTableRowGroup->addGroupHeader(StdLib::nvl($rowgroup['groupheader'],[]));
                foreach ($rowgroup['rows'] as $row) {
                    $soReportTableRowGroup->addRow(StdLib::nvl($row,[]));
                }
                $soReportTableRowGroup->addGroupFooter(StdLib::nvl($rowgroup['groupfooter'],[]));
                $this->SOReport->addTableRowGroup($soReportTableRowGroup);
            }
        }
        // футер таблицы
        if(isset($table['footers'])){
            foreach ($table['footers'] as $footer){
                $this->SOReport->addTableFooter(['content', $footer]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function makeReport()
    {

    }
}