<?php

/**
 * Class UtkDateTime
 * Реализует функциональность для работы с датами в формате УТК
 */
class UtkDateTime extends KFormModel
{
    /**
     * Получить дату в формате УТК
     * @param $dateStr
     * @return string
     */
    public static function getUtkDate($dateStr)
    {

        $format = (preg_match('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $dateStr))
            ? 'Y-m-d H:i:s'
            : 'Y-m-d';

        $utkDate = DateTime::createFromFormat($format, $dateStr);

        if (empty($utkDate)) {
            return '0000-00-00 00:00:00';
        } else {
            return $utkDate->format('Y-m-d\TH:i:s');
        }
    }

}

