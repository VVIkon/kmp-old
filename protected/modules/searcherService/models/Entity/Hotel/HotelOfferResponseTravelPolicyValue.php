<?php

/**
 * @property $ho_offers_id
 */
class HotelOfferResponseTravelPolicyValue extends AbstractTravelPolicyValue
{
    public function tableName()
    {
        return 'ho_offerValue';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @param HotelOfferResponse $offer
     * @return mixed
     */
    public function bindOffer(AbstractOffer $offer)
    {
        $this->ho_offers_id = $offer->getId();
    }
}