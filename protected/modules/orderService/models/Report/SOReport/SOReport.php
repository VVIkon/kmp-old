<?php

/**
 * Сущность данных отчета, структура so_report
 */
class SOReport implements Iterator
{
    /**
     * Главный заголовок
     * @var string
     */
    private $mainHeader;
    /**
     * Тексты в заголовке
     * @var array
     */
    private $headerTexts = [];
    /**
     * Тексты в футере
     * @var array
     */
    private $footerTexts = [];

    /**
     * Заголовки таблицы
     * @var array
     */
    private $tableHeaders = [];
    /**
     * Заголовки таблицы
     * @var array
     */
    private $tableFooters = [];

    /**
     * @var SOReportTableRowGroup[]
     */
    private $tableRowGroups = [];
    private $position = 0;

    /**
     * @param mixed $mainHeader
     * @return SOReport
     */
    public function setMainHeader($mainHeader)
    {
        $this->mainHeader = $mainHeader;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMainHeader()
    {
        return $this->mainHeader;
    }

    public function addHeaderTextRow(array $headerText)
    {
        $this->headerTexts[] = $headerText;
        return $this;
    }

    public function addFooterTextRow(array $footerText)
    {
        $this->footerTexts[] = $footerText;
        return $this;
    }


    /**
     * @return array
     */
    public function getHeaderTexts()
    {
        return $this->headerTexts;
    }

    /**
     * @return array
     */
    public function getFooterTexts()
    {
        return $this->footerTexts;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->tableRowGroups[$this->position];
    }

    public function next()
    {
        ++$this->position;
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @return mixed
     */
    public function valid()
    {
        return isset($this->tableRowGroups[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function addTableRowGroup(SOReportTableRowGroup $soReportTableRowGroup)
    {
        $this->tableRowGroups[] = $soReportTableRowGroup;
        return $this;
    }

    public function addTableHeader(array $tableHeader)
    {
        $this->tableHeaders[] = $tableHeader;
        return $this;
    }

    public function addTableFooter(array $tableFooter)
    {
        $this->tableFooters[] = $tableFooter;
        return $this;
    }
    /**
     * @return array
     */
    public function getTableHeaders()
    {
        return $this->tableHeaders;
    }

    /**
     * @return array
     */
    public function getTableFooters()
    {
        return $this->tableFooters;
    }

//    /**
//     *
//     * @return array
//     */
//    public function toArray()
//    {
//        return [
//            'mainHeader' => $this->mainHeader,
//            'headerTexts' => [],            // текст документа. Не более 100k (ориентировочно). Каждый элемент массива на своей строке Текст выводится над таблицей, под заголовком
//            'footerTexts' => [],             // текст подвала документа. Не более 100k (ориентировочно). Каждый элемент массива на своей строке Текст выводится под таблицей.
//            'table' => $this->table,
//            'footers' => $this->footers,
//        ];
//    }
}