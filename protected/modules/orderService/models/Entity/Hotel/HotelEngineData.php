<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/24/16
 * Time: 1:34 PM
 */
class HotelEngineData extends AbstractEngineData
{
    public function tableName()
    {
        return 'ho_engineData';
    }

    public function bindHotelOffer(HotelOffer $offer)
    {
        $this->offerId = $offer->getOfferId();
    }
}