<?php

/**
 * @property $fl_Offer_id
 */
class AviaOfferResponseTravelPolicyValue extends AbstractTravelPolicyValue
{
    public function tableName()
    {
        return 'fl_offerValue';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @param AviaOffer $offer
     * @return mixed
     */
    public function bindOffer(AbstractOffer $offer)
    {
        $this->fl_Offer_id = $offer->getId();
    }
}