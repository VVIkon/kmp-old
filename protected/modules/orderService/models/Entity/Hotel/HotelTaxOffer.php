<?php

/**
 *
 * @property $offerId
 */
class HotelTaxOffer extends AbstractTaxOffer
{
    public function tableName()
    {
        return 'kt_service_ho_taxOffer';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @param $priceOfferId
     * @return mixed
     */
    protected function setPriceId($priceOfferId)
    {
        $this->offerId = $priceOfferId;
    }
}