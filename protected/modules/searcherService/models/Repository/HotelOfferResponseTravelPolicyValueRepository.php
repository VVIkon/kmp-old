<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10.03.17
 * Time: 14:47
 */
class HotelOfferResponseTravelPolicyValueRepository
{
    public static function getByOfferId($offerId)
    {
        return HotelOfferResponseTravelPolicyValue::model()->findByPk($offerId);
    }
}