<?php

/**
 * Ценовые предложения для авиа
 *
 * @property $offerId
 */
class AviaOfferPrice extends AbstractPriceOffer
{
    public function tableName()
    {
        return 'kt_service_fl_priceOffer';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function bindOffer(AviaOffer $offer)
    {
        $this->offerId = $offer->getOfferId();
    }

    /**
     * @return mixed
     */
    public function getTaxes()
    {
        return [];
    }


}