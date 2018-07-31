<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/24/16
 * Time: 1:34 PM
 */
abstract class AbstractEngineData extends CActiveRecord
{
    protected $reservationId;
    protected $gateId;
    protected $offerKey;
    protected $offerId;
    protected $data;
    protected $date;

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function init()
    {
        $this->date = StdLib::getMysqlDateTime();
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

    /**
     * @return mixed
     */
    public function getGateId()
    {
        return $this->gateId;
    }

    /**
     * @param mixed $gateId
     */
    public function setGateId($gateId)
    {
        $this->gateId = $gateId;
    }

    /**
     * @return mixed
     */
    public function getOfferKey()
    {
        return $this->offerKey;
    }

    /**
     * @param mixed $offerKey
     */
    public function setOfferKey($offerKey)
    {
        $this->offerKey = $offerKey;
    }

    /**
     * @return mixed
     */
    public function getOfferId()
    {
        return $this->offerId;
    }

    /**
     * @param mixed $offerId
     */
    public function setOfferId($offerId)
    {
        $this->offerId = $offerId;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return json_decode($this->data, true);
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = json_encode($data);
    }

    public function toArray()
    {
        return [
            'reservationId' => $this->reservationId,    // Идентификатор брони
            'offerId' => $this->offerId,     // Идентификатор офера
            'offerKey' => $this->offerKey,      // Ключ офера
            'gateId' => $this->gateId,                  // тип внутреннего шлюза через который произведено бронирование услуги. 5 = GPTS (идентификаторы из kt_ref_gateways)
            'data' => $this->getData()
        ];
    }
}