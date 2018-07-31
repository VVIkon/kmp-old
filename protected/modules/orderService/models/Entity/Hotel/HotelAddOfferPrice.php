<?php

/**
 * @property $id
 * @property $addOfferId
 */
class HotelAddOfferPrice extends AbstractPriceOffer
{
    public function tableName()
    {
        return 'kt_service_ho_priceAddOffer';
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

    public function bindAddOffer(HotelAddOffer $addOffer)
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