<?php

/**
 * Class HotelGptsResponse
 * Класс для работы с данными размещения полученного от GPTS
 */
class HotelGptsResponse extends GptsResponse
{
    /** @var string ID предложения места размещения */
    public $offerkey;
    /** @var string Наименование места размещения */
    public $hotelName;
    /** @var string Адрес места размещения */
    public $address;
    /** @var int Категория места размещения */
    public $category;
    /** @var string Код поставщика предложения */
    public $supplierCode;
    /** @var string Код города по версии GPTS */
    public $cityCode;
    /** @var string Код отеля по версии поставщика предложения */
    public $hotelCode;
    /** @var float Широта размещения отеля в географических координатах */
    public $latitude;
    /** @var float Долгота размещения отеля в географических координатах */
    public $longitude;
    /** @var string URL изображения места размещения */
    public $mainImageUrl;
    /** @var array Информация о номерах места размещения */
    public $roomOffers;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module, $type)
    {
        parent::__construct($module, $type);
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Задать предложения номеров места размещения
     * @param $roomOffers
     * @return bool
     */
    public function setRoomOffers($roomOffers)
    {
        $this->roomOffers = [];

        foreach ($roomOffers as $roomOffer) {
            $this->roomOffers[] = new GptsRoomOffer($roomOffer);
        }

        return true;
    }

    /**
     * Инициализация свойств объекта ответа от GPTS
     * @param $request
     * @return bool
     */
    public function initParams($params)
    {
        //генерация ключа для связи всех предложений номеров в этом отеле
        //$hotelKey = Yii::app()->getSecurityManager()->generateRandomString(16, false);
        $this->offerkey = '';
        $this->hotelName = hashtableval($params['info']['name'],'');
        $this->address = hashtableval($params['info']['address'],'');
        $this->category = hashtableval($params['info']['category'],'');
        $this->supplierCode = hashtableval($params['info']['supplierCode'],'');
        $this->cityCode = hashtableval($params['info']['cityCode'],'');
        $this->hotelCode = hashtableval($params['info']['hotelCode'],'');
        $this->latitude = hashtableval($params['info']['latitude'],'');
        $this->longitude = hashtableval($params['info']['longitude'],'');
        $this->mainImageUrl = hashtableval($params['info']['mainImageUrl'],'');

        $this->setRoomOffers( hashtableval($params['roomOffers'],'') );
        return true;
    }

    /**
     * Конвертация параметров
     * @return array
     */
    public function toOffer($hotelId = null, $cityId = null, $curForm = null)
    {
        $offer = [];
        $offer['offerKey'] = $this->offerkey;
        $offer['address'] = $this->address;
        $offer['category'] = $this->category;
        $offer['mainImageUrl'] = $this->mainImageUrl;
        $offer['supplierCode'] = $this->supplierCode;
        $offer['hotelCode'] = $this->hotelCode;

        $offer['hotelNameRu'] = $this->hotelName;
        $offer['hotelNameEn'] = $this->hotelName;

        $offer['hotelId'] = $hotelId;
        $offer['cityId'] = $cityId;

        $offer['currencyBrutto'] = $this->currencyBrutto;
        $offer['amountBrutto'] = $this->priceBrutto;
        $offer['available'] = $this->available;
        $offer['lastTicketingDate'] = $this->lastTicketingDate;
        $offer['flightTariff'] = $this->flightTariff;
        $offer['offerDateTime'] = (new DateTime())->format('Y-m-d H:i:s');
        $offer['fareType'] = $this->fareType;

//        if (empty($curForm)) {
//            $curForm = new CurrencyForm();
//        }

        foreach ($this->roomOffers as $roomOffer) {
//            $roomOffer = $roomOffer->toArray($curForm);
            $roomOffer = $roomOffer->toArray();
            //$roomOffer['offerKey'] = $this->offerKey;
            $offer['roomOffers'][] = $roomOffer;
        }

        $offer = $this->applyRules($offer);
        return $offer;
    }

    /**
     * Применить дополнительные правила
     * конвертации ответа провайдера в предложение
     * @param $offer
     * @return mixed
     */
    public function applyRules($offer)
    {
        /*$setValidatingCompany = function (&$item, $key)
        {
            if ($key == 'validatingAirline') {
                $item = $this->validatingAirline;
            }
        };*/

        //array_walk_recursive($offer, $setValidatingCompany);

        return $offer;
    }

    /**
     * Получить нетто цену предложения
     * @param $salesTerms array детали стоимости
     * @return string
     */
    private function getNettoPrice($salesTerms)
    {
        return $this->getPrice($salesTerms, 'SUPPLIER');
    }

    /**
     * Получить брутто цену предложения
     * @param $salesTerms array детали стоимости
     * @return string
     */
    private function getBruttoPrice($salesTerms)
    {
        return $this->getPrice($salesTerms, 'CLIENT');
    }

    /**
     * Получить стоимость предложения
     * @param $salesTerms array детали стоимости
     * @param $priceType string тип цены(брутто|нетто)
     * @return string
     */
    private function getPrice($salesTerms, $priceType)
    {
        if (!empty($salesTerms) && is_array($salesTerms)) {
            foreach ($salesTerms as $saleTerm) {
                if (!empty($saleTerm['type']) && $saleTerm['type'] == $priceType) {
                    return isset($saleTerm['price']['amount']) ? $saleTerm['price']['amount'] : 0;
                }
            }
        }
    }

    /**
     * Получить валюту нетто стоимости предложения
     * @param $salesTerms
     * @return string
     */
    private function getNettoCurrency($salesTerms)
    {
        return $this->getCurrency($salesTerms, 'SUPPLIER');
    }

    /**
     * Получить валюту брутто стоимости предложения
     * @param $salesTerms
     * @return string
     */
    private function getBruttoCurrency($salesTerms)
    {
        return $this->getCurrency($salesTerms, 'CLIENT');
    }

    /**
     * Получить валюту стоимости предложения
     * @param $salesTerms array детали стоимости
     * @param $priceType string тип цены(брутто|нетто)
     * @return string
     */
    private function getCurrency($salesTerms, $priceType)
    {
        if (!empty($salesTerms) && is_array($salesTerms)) {
            foreach ($salesTerms as $saleTerm) {
                if (!empty($saleTerm['type']) && $saleTerm['type'] == $priceType) {
                    return isset($saleTerm['price']['currency']) ? $saleTerm['price']['currency'] : '';
                }
            }
        }
    }


}
