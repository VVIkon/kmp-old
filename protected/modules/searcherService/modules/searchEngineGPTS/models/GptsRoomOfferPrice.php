<?php

/**
 * Class GptsRoomOfferPrice
 * Реализует функциональность для работы с ценовыми параметрами
 * номера для предложения размещения
 */
class GptsRoomOfferPrice extends KFormModel
{
    const SUPPLIER_PRICE_TYPE = 1;
    const CLIENT_PRICE_TYPE = 2;

    /** @var string Тип назначения ценовых параметров */
    public $type;
    /** @var string Валюта предложения */
    public $currency;
    /** @var string Стоимость предложения нетто */
    public $amountNetto;
    /** @var string Стоимость предложения брутто */
    public $amountBrutto;
    /** @var string Валюта комиссии */
    public $commissionCurrency;
    /** @var string Размер комиссии в валюте комиссии */
    public $commissionAmount;
    /** @var string Коммиссия брутто цены в процентах */
    public $commissionPercent;
    /** @var int оригинальная валюта поставщика */
    public $originalCurrency;

    /**
     * @var GptsRoomOfferPriceTax[]
     */
    public $taxes;

    /**
     * @param $module object
     */
    public function __construct()
    {
    }

    /**
     * Вывести данные объекта в массив
     * @param $curForm
     * @return array
     */
    public function toArray($curForm = null)
    {
//        if (empty($curForm)) {
//            $curForm = new CurrencyForm();
//        }
        $curForm = CurrencyRates::getInstance();

        $taxesAndFees = [];

        if (isset($this->taxes)) {
            foreach ($this->taxes as $tax) {
                $taxesAndFees[] = $tax->toArray();
            }
        }

        $roomPrice['type'] = ($this->type == 'CLIENT') ? self::CLIENT_PRICE_TYPE : self::SUPPLIER_PRICE_TYPE;
        $roomPrice['currency'] = $curForm->getIdByCode($this->currency);
        $roomPrice['amountNetto'] = $this->amountNetto;
        $roomPrice['amountBrutto'] = $this->amountBrutto;
        $roomPrice['originalCurrency'] = $curForm->getIdByCode($this->originalCurrency);
        $roomPrice['commission']['currency'] = $curForm->getIdByCode($this->commissionCurrency);
        $roomPrice['commission']['amount'] = $this->commissionAmount;
        $roomPrice['commission']['percent'] = $this->commissionPercent;
        $roomPrice['taxesAndFees'] = $taxesAndFees;

        return  $roomPrice;
    }
}
