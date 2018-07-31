<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/13/16
 * Time: 1:25 PM
 */
class HotelPriceOfferRepository
{
    /**
     * @param $offerId
     * @param $type
     * @return HotelPriceOffer
     */
    public static function getPriceOfferByOfferIdAndType($offerId, $type)
    {
        return HotelPriceOffer::model()->findByAttributes(array('type' => $type, 'offerId' => $offerId));
    }
}