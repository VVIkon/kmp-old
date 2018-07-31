<?php

/**
 * Class ReportSpreadsheetPositionRange
 * Диапазон позиций
 */
class ReportSpreadsheetPositionRange
{
    /**
     * @var ReportSpreadsheetPosition
     */
    private $from;
    /**
     * @var ReportSpreadsheetPosition
     */
    private $to;

    public function __construct(ReportSpreadsheetPosition $from, ReportSpreadsheetPosition $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function __toString()
    {
        return $this->from . ':' . $this->to;
    }
}