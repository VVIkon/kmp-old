<?php

/**
 * @property $kt_service_ho_offers_offerId
 */
class HotelOfferTravelPolicyValue extends AbstractTravelPolicyValue
{
    public function tableName()
    {
        return 'kt_service_ho_offerValue';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @param HotelOffer $offer
     * @return mixed
     */
    public function bindOffer(AbstractOffer $offer)
    {
        $this->kt_service_ho_offers_offerId = $offer->getOfferId();
    }
}