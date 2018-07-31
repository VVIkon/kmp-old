<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/21/16
 * Time: 12:51 PM
 */
class AviaOfferRepository
{
    public static function findByOfferId($offerId)
    {
        return AviaOffer::model()->findByPk($offerId);
    }
}