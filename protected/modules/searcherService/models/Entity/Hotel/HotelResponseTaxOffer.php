<?php

/**
 * @property $code
 * @property $amount
 * @property $currency
 * @property $priceOfferId
 */
class HotelResponseTaxOffer extends AbstractTaxOffer
{
    public function tableName()
    {
        return 'ho_taxOffer';
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
        $this->priceOfferId = $priceOfferId;
    }


//    public function relations()
//    {
//        return array(
//            'hotelOffer' => array(self::BELONGS_TO, 'HotelOffer', 'offerKey')
////            'priceOffer' => array(self::BELONGS_TO, 'PriceOffer', 'offerKey')
//        );
//    }
}