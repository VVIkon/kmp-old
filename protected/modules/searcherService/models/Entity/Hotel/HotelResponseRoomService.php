<?php

/**
 * Услуги в номере
 *
 * @property $offerKey
 */
class HotelResponseRoomService extends AbstractRoomService
{
    public function tableName()
    {
        return 'ho_roomService';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'hotelOffer' => array(self::BELONGS_TO, 'HotelOffer', 'offerKey')
        );
    }

    /**
     * @param mixed $offerKey
     */
    public function setOfferKey($offerKey)
    {
        $this->offerKey = $offerKey;
    }
}