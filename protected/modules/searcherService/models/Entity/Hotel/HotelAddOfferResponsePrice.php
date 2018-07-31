<?php

/**
 * Цены для доп услуг в отеле
 *
 * @property $addOfferId
 */
class HotelAddOfferResponsePrice extends AbstractPriceOffer
{
    public function tableName()
    {
        return 'ho_priceAddOffer';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

//    public function relations()
//    {
//        return array(
//            'hotelOffer' => array(self::BELONGS_TO, 'HotelOfferResponse', 'offerId'),
//        );
//    }

    public function bindAddOffer(HotelAddOfferResponse $addOffer)
    {
        $this->addOfferId = $addOffer->getId();
    }

    /**
     * @return mixed
     */
    public function getTaxes()
    {
        return [];
    }


}