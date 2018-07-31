<?php

/**
 * Модель отношений туристов и брони
 */
class HotelReservationTourist extends CActiveRecord
{
    protected $touristId;
    protected $reservationId;

    public function tableName()
    {
        return 'kt_service_ho_reservationTourists';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return mixed
     */
    public function getTouristId()
    {
        return $this->touristId;
    }

    /**
     * @param mixed $touristId
     */
    public function setTouristId($touristId)
    {
        $this->touristId = $touristId;
    }

    /**
     * @return mixed
     */
    public function getReservationId()
    {
        return $this->reservationId;
    }

    /**
     * @param mixed $reservationId
     */
    public function setReservationId($reservationId)
    {
        $this->reservationId = $reservationId;
    }


}