<?php

/**
 * Class HotelResponseHandler
 * Класс для формирования возвращаемой информации по предложениям размещения
 */
class HotelResponseHandler extends ResponseHandler
{

    /**
     * Тип предложения в ответе от провайдера
     * @var
     */
    protected $offerType;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        parent::__construct($module);
    }

    public function __get($name)
    {
        switch ($name) {
            case 'offerType' :
                return $this->offerType;
                break;
        }
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }

    /**
     * Получить предложения размещения по указанному токену
     * @param string $token токен поиска
     * @param srting $lang код языка
     * @param string $currency код валюты
     * @return array objects
     */
    public function getOffersByToken($token, $lang, $currency)
    {
        $offersList = [];

        $hotelsOffers = RoomOffer::getHotelsOffers($token);
        $offersHotelsInfo = HotelProviderOffer::getOffersHotelsInfo($token);
        $offersPrices = RoomOfferPrice::getOffersPrices($token);

        $CurrencyRates = CurrencyRates::getInstance();

        foreach ($hotelsOffers as $hotelId => $ho) {
            $hotelOffer = $offersHotelsInfo[$hotelId];
            $hotelOffer['roomOffers'] = [];

            foreach ($ho as $offer) {
                $offer['salesTerms'] = $offersPrices[$offer['offerKey']];
                $hotelOffer['roomOffers'][] = $offer;
            }

            $hpo = new HotelProviderOffer($this->module);
            $hpo->initParams($hotelOffer);

            $offersList[] = $hpo->toArray($lang, $CurrencyRates->getIdByCode($currency), $CurrencyRates);
//
//            var_dump($offersList);
//            exit;
        }

        return $offersList;
    }

    /**
     * Сформировать информацию по предложениям размещения по указанным параметрам
     * @param string $token токен поиска
     * @param srting $lang код языка
     * @param string $currency код валюты
     */
    public function getOffersInfoByToken($token, $lang, $currency, $params)
    {

        return $this->getOffersByToken($token, $lang, $currency, $params);
    }
}
