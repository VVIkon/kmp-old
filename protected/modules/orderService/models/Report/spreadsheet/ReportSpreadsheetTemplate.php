<?php
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportSpreadsheetTemplate
{
    private $template;

    public function __construct($template)
    {
        $this->template = $template;
    }

    /**
     * "font": {
     *      "size": 12,
     *      "color": "EE1234",
     *      "backColor": "EE1234",
     *      "style": "underline"
     *  },
     * "cell": {
     *      "valign": "up",
     *      "halign": "left",
     *      "dataFormat": "text",
     *      "indent": 2
     * },
     * "imageUrl": ""
     *
     * @param $templateCellStyle
     * @return array
     */
    private function templateCellStyleToStyleArray($templateCellStyle)
    {
        $styleArr = [];

        /**
         * FONT
         */
        if (isset($templateCellStyle['font']['size']) && $templateCellStyle['font']['size']) {
            $styleArr['font']['size'] = $templateCellStyle['font']['size'];
        }
        if (isset($templateCellStyle['font']['color']) && $templateCellStyle['font']['color']) {
            $styleArr['font']['color']['rgb'] = $templateCellStyle['font']['color'];
        }
        if (isset($templateCellStyle['font']['backColor']) && $templateCellStyle['font']['backColor']) {
            $styleArr['fill']['fillType'] = Fill::FILL_SOLID;
            $styleArr['fill']['startColor']['rgb'] = $templateCellStyle['font']['backColor'];
            $styleArr['fill']['endColor']['rgb'] = $templateCellStyle['font']['backColor'];
        }

        $fontStyle = isset($templateCellStyle['font']['style']) ? $templateCellStyle['font']['style'] : '';

        switch ($fontStyle) {
            case 'underline':
                $styleArr['font']['underline'] = true;
                break;
            case 'bold':
                $styleArr['font']['bold'] = true;
                break;
//            case 'plain':
//                break;
            case 'italic':
                $styleArr['font']['italic'] = true;
                break;
            default;
                break;
        }

        /**
         * alignment
         */
        $valignment = isset($templateCellStyle['cell']['valign']) ? $templateCellStyle['cell']['valign'] : '';
        switch ($valignment) {
            case 'up':
                $styleArr['alignment']['vertical'] = Style\Alignment::VERTICAL_TOP;
                break;
            case 'center':
                $styleArr['alignment']['vertical'] = Style\Alignment::VERTICAL_CENTER;
                break;
            case 'down':
                $styleArr['alignment']['vertical'] = Style\Alignment::VERTICAL_BOTTOM;
                break;
        }

        /**
         * halignment
         */
        $halignment = isset($templateCellStyle['cell']['halign']) ? $templateCellStyle['cell']['halign'] : '';
        switch ($halignment) {
            case 'left':
                $styleArr['alignment']['horizontal'] = Style\Alignment::HORIZONTAL_LEFT;
                break;
            case 'center':
                $styleArr['alignment']['horizontal'] = Style\Alignment::HORIZONTAL_CENTER;
                break;
            case 'right':
                $styleArr['alignment']['horizontal'] = Style\Alignment::HORIZONTAL_RIGHT;
                break;
        }

        /**
         * indent
         */
        if(isset($templateCellStyle['cell']['indent']) && $templateCellStyle['cell']['indent']){
            $styleArr['alignment']['horizontal'] = Style\Alignment::HORIZONTAL_LEFT;
            $styleArr['alignment']['indent'] = $templateCellStyle['cell']['indent'];
        }


        return $styleArr;
    }

    public function getMainHeaderStyle()
    {
        $mainHeaderTextStyle = isset($this->template['mainHeaderTextStyle']) ? $this->template['mainHeaderTextStyle'] : [];
        return $this->templateCellStyleToStyleArray($mainHeaderTextStyle);
    }

    public function getHeaderTextStyleForRow($rowNum)
    {
        $headerTextStyle = isset($this->template['headerTextStyles'][$rowNum]) ? $this->template['headerTextStyles'][$rowNum] : [];
        return $this->templateCellStyleToStyleArray($headerTextStyle);
    }

    public function getFooterTextStyleForRow($rowNum)
    {
        $headerTextStyle = isset($this->template['footerTextStyles'][$rowNum]) ? $this->template['footerTextStyles'][$rowNum] : [];
        return $this->templateCellStyleToStyleArray($headerTextStyle);
    }

    public function getHeaderRowStyleByRowAndClmn($row, $clmn)
    {
        $headerTextStyle = isset($this->template['columns'][$clmn]['headerRows'][$row]) ? $this->template['columns'][$clmn]['headerRows'][$row] : [];
        return $this->templateCellStyleToStyleArray($headerTextStyle);
    }

    public function getFooterRowStyleByRowAndClmn($row, $clmn)
    {
        $headerTextStyle = isset($this->template['columns'][$clmn]['footerRows'][$row]) ? $this->template['columns'][$clmn]['footerRows'][$row] : [];
        return $this->templateCellStyleToStyleArray($headerTextStyle);
    }

    public function getGroupedHeaderRowStyle($clmn)
    {
        $headerTextStyle = isset($this->template['columns'][$clmn]['grouppedHeaderRow']) ? $this->template['columns'][$clmn]['grouppedHeaderRow'] : [];
        return $this->templateCellStyleToStyleArray($headerTextStyle);
    }

    public function getGroupedFooterRowStyle($clmn)
    {
        $headerTextStyle = isset($this->template['columns'][$clmn]['grouppedFooterRow']) ? $this->template['columns'][$clmn]['grouppedFooterRow'] : [];
        return $this->templateCellStyleToStyleArray($headerTextStyle);
    }

    public function getRowStyle($clmn)
    {
        $headerTextStyle = isset($this->template['columns'][$clmn]['grouppedRow']) ? $this->template['columns'][$clmn]['grouppedRow'] : [];
        return $this->templateCellStyleToStyleArray($headerTextStyle);
    }

    public function getTaggedRowFooterStyle($tag)
    {
        $tagStyle = isset($this->template[$tag]['grouppedFooterRow']) ? $this->template[$tag]['grouppedFooterRow'] : [];
        return $this->templateCellStyleToStyleArray($tagStyle);
    }

    public function getTaggedRowHeaderStyle($tag)
    {
        $tagStyle = isset($this->template[$tag]['grouppedHeaderRow']) ? $this->template[$tag]['grouppedHeaderRow'] : [];
        return $this->templateCellStyleToStyleArray($tagStyle);
    }

    public function getTaggedRowStyle($tag)
    {
        $tagStyle = isset($this->template[$tag]['grouppedRow']) ? $this->template[$tag]['grouppedRow'] : [];
        return $this->templateCellStyleToStyleArray($tagStyle);
    }

    public function getTaggedHeaderRowStyle($row, $tag)
    {
        $tagStyle = isset($this->template[$tag]['headerRows'][$row]) ? $this->template[$tag]['headerRows'][$row] : [];
        return $this->templateCellStyleToStyleArray($tagStyle);
    }

    public function getTaggedFooterRowStyle($row, $tag)
    {
        $tagStyle = isset($this->template[$tag]['footerRows'][$row]) ? $this->template[$tag]['footerRows'][$row] : [];
        return $this->templateCellStyleToStyleArray($tagStyle);
    }

    public function getTaggedColumnWidth($tag)
    {
        return isset($this->template[$tag]['width']) ? $this->template[$tag]['width'] : -1;
    }

    public function getColumnWidth($clmn)
    {
        return isset($this->template['columns'][$clmn]['width']) ? $this->template['columns'][$clmn]['width'] : -1;
    }

    public function getMainHeaderImage()
    {
        return isset($this->template['mainHeaderTextStyle']['imageUrl']) ? $this->template['mainHeaderTextStyle']['imageUrl'] : '';
    }
}