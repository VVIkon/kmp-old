<?php

/**
 * @property $kt_service_fl_Offer_offerID
 */
class AviaOfferTravelPolicyValue extends AbstractTravelPolicyValue
{
    public function tableName()
    {
        return 'kt_service_fl_offerValue';
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
        $this->kt_service_fl_Offer_offerID = $offer->getOfferId();
    }
}