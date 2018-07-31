<?php

/**
 * Штрафы отельные
 */
class HotelCancelPenalty extends AbstractCancelPenalty
{
    public function tableName()
    {
        return 'kt_service_ho_cancelPenalties';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function isActual()
    {
        $dateFrom = new DateTime($this->dateTimeFrom);
        $dateTo = new DateTime($this->dateTimeTo);

        return $dateFrom->getTimestamp() <= time() && $dateTo->getTimestamp() >= time();
    }
}