<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/15/16
 * Time: 6:00 PM
 */
class HotelResponsePriceOfferByDay extends AbstractPriceOffer
{
    public function tableName()
    {
        return 'ho_priceOfferByDay';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'hotelOffer' => array(self::BELONGS_TO, 'HotelOffer', 'offerKey')
            , 'priceOfferByDaysCurrency' => array(self::BELONGS_TO, 'Currency', 'currency')
        );
    }

    public function getDate()
    {
        return $this->getAttribute('date');
    }
}