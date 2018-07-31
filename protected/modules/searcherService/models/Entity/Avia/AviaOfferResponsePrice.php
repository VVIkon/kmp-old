<?php

/**
 * Модель заглушка ценовых компонентов авиа респонса
 */
class AviaOfferResponsePrice extends AbstractPriceOffer
{
    public function tableName()
    {
        return 'kt_service_fl_priceOffer';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function bindOffer(AviaOfferResponse $offer)
    {
        $this->offerId = $offer->getId();
    }

    /**
     * @return mixed
     */
    public function getTaxes()
    {
        return [];
    }
}