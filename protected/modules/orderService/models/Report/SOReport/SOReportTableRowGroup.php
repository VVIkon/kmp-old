<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 29.09.17
 * Time: 13:44
 */
class SOReportTableRowGroup implements Iterator
{
    private $groupHeaders = [];
    private $rows = [];
    private $groupFooters = [];

    private $position = 0;

    public function addRow($row)
    {
        $this->rows[] = $row;
        return $this;
    }

    public function addGroupHeader(array $groupHeader)
    {
        $this->groupHeaders[] = $groupHeader;
    }

    public function addGroupFooter(array $groupFooter)
    {
        $this->groupFooters[] = $groupFooter;
    }

    /**
     * @return array
     */
    public function getGroupHeaders()
    {
        return $this->groupHeaders;
    }

    /**
     * @return array
     */
    public function getGroupFooters()
    {
        return $this->groupFooters;
    }

    /**
     * @return array
     */
    public function getGroupRows()
    {
        return $this->rows;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->rows[$this->position];
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
        return isset($this->rows[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }
}