<?php

/**
 * @property HotelResponseTaxOffer[] $taxes
 */
class HotelResponsePriceOffer extends AbstractPriceOffer
{
    public function tableName()
    {
        return 'ho_priceOffer';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'hotelOffer' => array(self::BELONGS_TO, 'HotelOffer', 'offerKey'),
            'PriceOfferCurrency' => array(self::BELONGS_TO, 'Currency', 'currency'),
            'taxes' => array(self::HAS_MANY, 'HotelResponseTaxOffer', 'priceOfferId'),
        );
    }

    /**
     * @return HotelResponseTaxOffer[]
     */
    public function getTaxes()
    {
        return $this->taxes;
    }
}