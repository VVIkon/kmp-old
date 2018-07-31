<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 14.06.17
 * Time: 15:06
 */
class AviaPriceOfferRepository
{
    /**
     * @param $offerId
     * @param $type
     * @return HotelPriceOffer
     */
    public static function getPriceOfferByOfferIdAndType($offerId, $type)
    {
        return AviaOfferPrice::model()->findByAttributes(array('type' => $type, 'offerId' => $offerId));
    }
}