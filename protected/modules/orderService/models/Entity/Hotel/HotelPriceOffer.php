<?php

/**
 * Created by PhpStorm.
 * @property Currency $PriceOfferCurrency
 * @property $offerId
 *
 * @property HotelTaxOffer[] $taxes
 */
class HotelPriceOffer extends AbstractPriceOffer
{

    public function tableName()
    {
        return 'kt_service_ho_priceOffer';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return array(
            'HotelOffer' => array(self::BELONGS_TO, 'HotelOffer', 'offerId'),
            'taxes' => array(self::HAS_MANY, 'HotelTaxOffer', 'offerId'),
        );
    }

    /**
     * @param mixed $offerId
     */
    public function setOfferId($offerId)
    {
        $this->offerId = $offerId;
    }

    /**
     * Обновление цены предложения
     * Делается при бронировании, когда с бронью приходит новая цена предложения
     * @param array $newPrice
     */
    public function updatePrice(array $newPrice)
    {
        $CurrencyRates = CurrencyRates::getInstance();
        $newCurrencyId = $CurrencyRates->getIdByCode($newPrice['price']['currency']);
        $this->amountBrutto = $CurrencyRates->calculateInCurrencyByIds($newPrice['price']['amount'], $newCurrencyId, $this->currency);
        $this->amountNetto = $this->amountBrutto - $this->commissionAmount;
    }

    /**
     * @return mixed
     */
    public function getTaxes()
    {
        return $this->taxes;
    }
}