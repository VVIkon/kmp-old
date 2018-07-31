<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 12/12/16
 * Time: 5:39 PM
 */
class AviaOfferPNRRepository
{
    /**
     * @param $PNR
     * @return null|AviaOfferPNR
     */
    public static function getByPNR($PNR)
    {
        return AviaOfferPNR::model()->findByPk($PNR);
    }
}