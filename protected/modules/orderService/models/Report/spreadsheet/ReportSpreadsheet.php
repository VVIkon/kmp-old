<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 14.09.17
 * Time: 11:02
 */
class ReportSpreadsheet
{
    /**
     * @var ReportSpreadsheetPosition
     */
    private $cursor;

    /**
     * @var ReportSpreadsheetTemplate
     */
    private $template;

    /**
     * @var ReportSpreadsheetPosition
     */
    private $tablePositionStart;

    /**
     * @var ReportSpreadsheetPosition
     */
    private $tablePositionEnd;

    /**
     * @var DirectoryIterator
     */
    private $templateOtherFiles;

    public function __construct($template, DirectoryIterator $templateOtherFiles)
    {
        $this->cursor = new ReportSpreadsheetPosition();
        $this->template = new ReportSpreadsheetTemplate($template);
        $this->templateOtherFiles = $templateOtherFiles;
    }

    /**
     *
     * @param SOReport $so_report
     * @return Spreadsheet
     */
    public function render(SOReport $so_report)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        /**
         * заголовок
         */
        $sheet->setCellValue((string)$this->cursor, $so_report->getMainHeader());
        $style = $this->template->getMainHeaderStyle();
        $sheet->getStyle($this->cursor)->applyFromArray($style);
        $rowHeight = isset($style['font']['size']) ? round($style['font']['size'] * 1.4) : 0;
        if ($rowHeight) {
            $sheet->getRowDimension(1)->setRowHeight($rowHeight);
        }

        // нарисуем картинку
        $imageName = $this->template->getMainHeaderImage();
        if ($imageName) {
            $image = $this->getImageWithName($imageName);
            if ($image) {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Logo');
                $drawing->setPath($image->getRealPath());
                $drawing->setHeight(35);
                $drawing->setCoordinates((string)$this->cursor);
                $drawing->setWorksheet($sheet);
            } else {
                throw new ReportException("Картинка $imageName не найдена в каталоге с шаблоном");
            }
        }

        $this->cursor->incRow(2);

        /**
         * подзаголовок
         */
        $headerTextRows = $so_report->getHeaderTexts();
        foreach ($headerTextRows as $rowNum => $headerTextRow) {
            $rowStartCursor = clone $this->cursor;

            foreach ($headerTextRow as $headerTextClmn) {
                $sheet->setCellValue((string)$this->cursor, $headerTextClmn);
                $this->cursor->incClmn();
            }
            $sheet->getStyle(new ReportSpreadsheetPositionRange($rowStartCursor, $this->cursor))->applyFromArray($this->template->getHeaderTextStyleForRow($rowNum));

            $this->cursor->enter();
        }

        $this->cursor->incRow(3);

        /**
         * заголовок таблицы
         */
        $tableHeaders = $so_report->getTableHeaders();
        $this->tablePositionStart = clone $this->cursor;

        foreach ($tableHeaders as $tableHeaderRowNum => $tableHeaderRow) {
            $addFieldCnt = 0;

            foreach ($tableHeaderRow as $tableHeaderClmnNum => $tableHeaderClmn) {
                $margin = 1;
                if (is_array($tableHeaderClmn)) {
                    $cellValue = $tableHeaderClmn[0];
                    $margin = isset($tableHeaderClmn['margin']) ? $tableHeaderClmn['margin'] : 1;
                    $tag = isset($tableHeaderClmn['tag']) ? $tableHeaderClmn['tag'] : '';
                    if ($tag) {
                        $sheet->getColumnDimension($this->cursor->getClmn())->setWidth($this->template->getTaggedColumnWidth($tag));
                    } else {
                        $sheet->getStyle($this->cursor)->applyFromArray($this->template->getHeaderRowStyleByRowAndClmn($tableHeaderRowNum, $tableHeaderClmnNum - $addFieldCnt));
                    }
                    $sheet->getStyle($this->cursor)->applyFromArray($this->template->getTaggedHeaderRowStyle($tableHeaderRowNum, $tag));

                    $addFieldCnt++;
                } else {
                    $cellValue = $tableHeaderClmn;
                    $sheet->getStyle($this->cursor)->applyFromArray($this->template->getHeaderRowStyleByRowAndClmn($tableHeaderRowNum, $tableHeaderClmnNum - $addFieldCnt));
                    $sheet->getColumnDimension($this->cursor->getClmn())->setWidth($this->template->getColumnWidth($tableHeaderClmnNum - $addFieldCnt));
                }
                $sheet->setCellValue((string)$this->cursor, $cellValue);

                if ($margin > 1) {
                    $sheet->mergeCells($this->cursor . ':' . $this->cursor->getIncrementedBy($margin - 1, 0));
                }

                $this->cursor->incClmn($margin);
            }
            $this->cursor->enter();
        }

        /**
         * Сама таблица из группы строк
         */
        foreach ($so_report as $rowGroup) {
            $this->writeRowGroup($sheet, $rowGroup);
        }

        /**
         * футер таблицы
         */
        $tableFooters = $so_report->getTableFooters();

        foreach ($tableFooters as $tableFooterRowNum => $tableFooterRow) {
            $addFieldCnt = 0;

            foreach ($tableFooterRow as $tableFooterClmnNum => $tableFooterClmn) {
                $margin = 1;

                if (is_array($tableFooterClmn)) {
                    $cellValue = $tableFooterClmn[0];
                    $margin = isset($tableFooterClmn['margin']) ? $tableFooterClmn['margin'] : 1;
                    $tag = isset($tableFooterClmn['tag']) ? $tableFooterClmn['tag'] : '';
                    if ($tag) {
                        $sheet->getStyle($this->cursor)->applyFromArray($this->template->getTaggedFooterRowStyle($tableFooterRowNum, $tag));
                    } else {
                        $sheet->getStyle($this->cursor)->applyFromArray($this->template->getFooterRowStyleByRowAndClmn($tableFooterRowNum, $tableFooterClmnNum - $addFieldCnt));
                    }

                    $addFieldCnt++;
                } else {
                    $cellValue = $tableFooterClmn;
                    $sheet->getStyle($this->cursor)->applyFromArray($this->template->getFooterRowStyleByRowAndClmn($tableFooterRowNum, $tableFooterClmnNum - $addFieldCnt));
                }
                $sheet->setCellValue((string)$this->cursor, $cellValue);

                if ($margin > 1) {
                    $sheet->mergeCells($this->cursor . ':' . $this->cursor->getIncrementedBy($margin - 1, 0));
                }

                // применим стиль к ячейке
                $sheet->getStyle($this->cursor)->applyFromArray($this->template->getFooterRowStyleByRowAndClmn($tableFooterRowNum, $tableFooterClmnNum));
                $this->cursor->incClmn($margin);
            }
            $this->cursor->enter();
        }
        $this->tablePositionEnd = new ReportSpreadsheetPosition($sheet->getHighestColumn(), $sheet->getHighestRow());

        $this->cursor->incRow(2);

        /**
         * футер
         */
        $footerTextRows = $so_report->getFooterTexts();
        foreach ($footerTextRows as $rowNum => $footerTextRow) {
            $rowStartCursor = clone $this->cursor;

            foreach ($footerTextRow as $footerTextClmn) {
                $sheet->setCellValue((string)$this->cursor, $footerTextClmn);
                $this->cursor->incClmn();
            }
            $sheet->getStyle(new ReportSpreadsheetPositionRange($rowStartCursor, $this->cursor))->applyFromArray($this->template->getFooterTextStyleForRow($rowNum));
            $this->cursor->enter();
        }


        // очистка всех границ
        $sheet->getStyle($sheet->calculateWorksheetDimension())->applyFromArray([
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => Border::BORDER_NONE
                ),
            )
        ]);
        // отрисовка границ таблицы
        $sheet->getStyle(new ReportSpreadsheetPositionRange($this->tablePositionStart, $this->tablePositionEnd))->applyFromArray([
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => Border::BORDER_THIN
                ),
            )
        ]);
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setWrapText(true);
        // colspan заголовка
        $sheet->mergeCells(new ReportSpreadsheetPositionRange(new ReportSpreadsheetPosition(), new ReportSpreadsheetPosition($this->tablePositionEnd->getClmn())));

        return $spreadsheet;
    }

    /**
     * Запись группы строк в документ
     * @param $sheet
     * @param SOReportTableRowGroup $rowGroup
     * @param $indent
     */
    private function writeRowGroup(Worksheet $sheet, SOReportTableRowGroup $rowGroup, $indent = -1)
    {
        ++$indent;

        // обозначим заголовок группы строк
        $tableHeaders = $rowGroup->getGroupHeaders();
        foreach ($tableHeaders as $tableHeaderRow) {
            $addFieldCnt = 0;

            foreach ($tableHeaderRow as $clmnNum => $tableHeaderRowClmn) {
                if (is_array($tableHeaderRowClmn)) {
                    $addFieldCnt++;
                    $sheet->setCellValue((string)$this->cursor, $tableHeaderRowClmn[0]);
                    $tag = isset($tableHeaderRowClmn['tag']) ? $tableHeaderRowClmn['tag'] : '';
                    $sheet->getStyle($this->cursor)->applyFromArray($this->template->getTaggedRowHeaderStyle($tag));
                } else {
                    $sheet->setCellValue((string)$this->cursor, $tableHeaderRowClmn);
                    $sheet->getStyle($this->cursor)->applyFromArray($this->template->getGroupedHeaderRowStyle($clmnNum - $addFieldCnt));
                }

                if ($clmnNum == 0) {
                    $sheet->getStyle($this->cursor)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent($indent);
                    $sheet->getStyle($this->cursor)->getAlignment()->setIndent($indent);
                }
                $this->cursor->incClmn();
            }
            $this->cursor->enter();   // с новой строки
        }

        // обозначим саму строку
        $tableRows = $rowGroup->getGroupRows();
        foreach ($tableRows as $row) {   // строка
            if ($row instanceof SOReportTableRowGroup) {
                $this->writeRowGroup($sheet, $row, $indent);
            } else {
                $addFieldCnt = 0;

                foreach ($row as $clmnNum => $clmn) {    // колонка
                    if (is_array($clmn)) {
                        $addFieldCnt++;
                        $sheet->setCellValue((string)$this->cursor, $clmn[0]);
                        $tag = isset($clmn['tag']) ? $clmn['tag'] : '';
                        $sheet->getStyle($this->cursor)->applyFromArray($this->template->getTaggedRowStyle($tag));
                    } else {
                        $sheet->setCellValue((string)$this->cursor, $clmn);
                        $sheet->getStyle($this->cursor)->applyFromArray($this->template->getRowStyle($clmnNum - $addFieldCnt));
                    }

                    if ($clmnNum == 0) {
                        $sheet->getStyle($this->cursor)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent($indent);
                        $sheet->getStyle($this->cursor)->getAlignment()->setIndent($indent + 1);
                    }

                    $this->cursor->incClmn();
                }
                $this->cursor->enter();   // с новой строки
            }
        }

        // обозначим футер группы строк
        $tableFooters = $rowGroup->getGroupFooters();
        foreach ($tableFooters as $tableFooterRow) {
            $addFieldCnt = 0;

            foreach ($tableFooterRow as $clmnNum => $tableFooterRowClmn) {
                if (is_array($tableFooterRowClmn)) {
                    $addFieldCnt++;
                    $tag = isset($tableFooterRowClmn['tag']) ? $tableFooterRowClmn['tag'] : '';
                    $sheet->setCellValue((string)$this->cursor, $tableFooterRowClmn[0]);
                    $sheet->getStyle($this->cursor)->applyFromArray($this->template->getTaggedRowFooterStyle($tag));
                } else {
                    $sheet->setCellValue((string)$this->cursor, $tableFooterRowClmn);
                    $sheet->getStyle($this->cursor)->applyFromArray($this->template->getGroupedFooterRowStyle($clmnNum - $addFieldCnt));
                }

                if ($clmnNum == 0) {
                    $sheet->getStyle($this->cursor)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setIndent($indent);
                    $sheet->getStyle($this->cursor)->getAlignment()->setIndent($indent);
                }

                $this->cursor->incClmn();
            }
            $this->cursor->enter();
        }
    }

    private
    function getImageWithName($name)
    {
        foreach ($this->templateOtherFiles as $templateOtherFile) {
            if ($templateOtherFile->getFilename() == $name) {
                return $templateOtherFile;
            }
        }

        return false;
    }
}