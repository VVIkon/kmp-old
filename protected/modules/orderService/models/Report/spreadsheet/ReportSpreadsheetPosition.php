<?php

/**
 * Класс определяет текущую позицию курсора
 */
class ReportSpreadsheetPosition
{
    private $clmn = 'A';
    private $row = 1;

    public function __construct($clmn = 'A', $row = 1)
    {
        $this->clmn = $clmn;
        $this->row = $row;
    }

    public function getClmn()
    {
        return $this->clmn;
    }

    public function getRow()
    {
        return $this->row;
    }

    public function enter()
    {
        $this->resetClmn();
        $this->incRow();
    }

    public function getIncrementedBy($clmns, $rows)
    {
        return ($this->increment($this->clmn, $clmns) . ($this->row + $rows));
    }

    public function __toString()
    {
        return $this->clmn . $this->row;
    }

    public function incClmn($clmns = 1)
    {
        return $this->clmn = $this->increment($this->clmn, $clmns);
    }

    public function incRow($row = 1)
    {
        $this->row += $row;
    }

    public function resetClmn()
    {
        $this->clmn = 'A';
    }

    private function increment($clmn, $positions)
    {
        for ($i = 1; $i <= $positions; $i++) {
            $clmn++;
        }
        return $clmn;
    }
}