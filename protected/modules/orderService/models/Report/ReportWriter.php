<?php

use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf;

class ReportWriter
{

    /**
     * @param $format
     * @param $spreadsheet
     * @return IWriter
     */
    public static function getConcreteWriter($format, $spreadsheet)
    {
        switch ($format){
            case 'xlsx':
                return new Xlsx($spreadsheet);
            case 'pdf':
                $rendererName = \PhpOffice\PhpSpreadsheet\Settings::PDF_RENDERER_MPDF;
                \PhpOffice\PhpSpreadsheet\Settings::setPdfRendererName($rendererName);

                $spreadsheet->getActiveSheet()->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $spreadsheet->getActiveSheet()->getPageSetup()
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

                return new Pdf($spreadsheet);
        }
    }
}