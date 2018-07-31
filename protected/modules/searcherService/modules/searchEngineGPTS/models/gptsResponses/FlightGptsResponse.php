<?php

/**
 * Class FlightGptsResponse
 * Класс для работы с данными авиабилета полученного от GPTS
 */
class FlightGptsResponse extends GptsResponse
{

    /**
     * Идентификатор предложения по версии поставщика
     * @var string
     */
    private $offerKey;

    /**
     * Стоимость предложения нетто
     * @var float
     */
    private $priceNetto;

    /**
     * Стоимость предложения брутто
     * @var float
     */
    private $priceBrutto;

    /**
     * Валюта предложения нетто
     * @var string
     */
    private $currencyNetto;

    /**
     * Валюта предложения брутто
     * @var string
     */
    private $currencyBrutto;

    /** @var float комиссия агента */
    private $commission;

    /** @var string валюта комиссии агента */
    private $commissionCurrency;

    /**
     * Признак наличия предложения
     * @var int
     */
    private $available;

    /**
     * Код валидации авиалинии
     * @var string
     */
    private $validatingAirline;

    /**
     * Последняя дата приобретения билета
     * @var string
     */
    private $lastTicketingDate;

    /**
     * Код поставщика предложения
     * @var string
     */
    private $supplierCode;

    /**
     * Тариф перелёта
     * @var strig
     */
    private $flightTariff;

    /**
     * Маршрут перелёта
     * @var array
     */
    private $route;

    /**
     * Тип тарифа
     * @var
     */
    private $fareType;

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
     * Задать маршрут для конкретного предложения
     * @param $tripType
     * @param $trips
     * @return bool
     */
    public function setRoute($trips)
    {
        $this->route = [];

        foreach ($trips as $trip) {
            $this->route[] = new GptsFlightTrip($trip['duration'], $trip['segments']);
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
        $this->offerKey = isset($params['offerKey']) ? $params['offerKey'] : '';

        $this->priceNetto = 0;
        $this->currencyNetto = '';
        $this->priceBrutto = 0;
        $this->currencyBrutto = '';
        $this->commission = 0;
        $this->commissionCurrency = '';

        /** @todo драфт обработки salesTerms */
        if (!empty($params['salesTerms']) && is_array($params['salesTerms'])) {
          foreach ($params['salesTerms'] as $saleTerm) {
              if (!empty($saleTerm['type']) && $saleTerm['type'] == 'SUPPLIER') {

                $this->priceNetto=isset($saleTerm['price']['amount']) ? $saleTerm['price']['amount'] : 0;
                $this->currencyNetto=isset($saleTerm['price']['currency']) ? $saleTerm['price']['currency'] : '';

              } elseif (!empty($saleTerm['type']) && $saleTerm['type'] == 'CLIENT') {

                $this->priceBrutto=isset($saleTerm['price']['amount']) ? $saleTerm['price']['amount'] : 0;
                $this->currencyBrutto=isset($saleTerm['price']['currency']) ? $saleTerm['price']['currency'] : '';

                if (!empty($saleTerm['price']['commission']) && is_array($saleTerm['price']['commission'])) {

                  $this->commission=isset($saleTerm['price']['commission']['amount']) ?
                    $saleTerm['price']['commission']['amount'] : 0;
                  $this->commissionCurrency=isset($saleTerm['price']['currency']) ?
                    $saleTerm['price']['commission']['currency'] : '';

                } else {
                  $this->commissionCurrency=$this->currencyBrutto;
                }

              }
          }
        }

        /**
        * @todo throw that shit >.<
        */
        /*
        $this->priceNetto = isset($params['salesTerms']) ? $this->getNettoPrice($params['salesTerms']) : '0';
        $this->currencyNetto  = isset($params['salesTerms']) ? $this->getNettoCurrency($params['salesTerms']) : '';

        $this->priceBrutto = isset($params['salesTerms']) ? $this->getBruttoPrice($params['salesTerms']) : '0';
        $this->currencyBrutto  = isset($params['salesTerms']) ? $this->getBruttoCurrency($params['salesTerms']) : '';
        */

        $this->available = isset($params['available']) ? $params['available'] : '';

        $this->validatingAirline = isset($params['validatingAirline']) ? $params['validatingAirline'] : '';

        $this->lastTicketingDate = isset($params['lastTicketingDate']) ? $params['lastTicketingDate'] : '';

        if (!empty($params['supplierCode'])) {
            $supplierCodes = $this->module->getConfig('suppliers_gpts_codes');
            $this->supplierCode = (array_key_exists($params['supplierCode'], $supplierCodes))
                ?  $supplierCodes[$params['supplierCode']]
                : '';
        } else {
            $this->supplierCode = '';
        }

        $this->flightTariff = isset($params['flightTariff']) ? $params['flightTariff'] : '';
        $this->fareType = '';

        $this->setRoute($params['itinerary']);
        return true;
    }

    /**
     * Конвертация параметров
     * @return array
     */
    public function toOffer()
    {
        $offer = [];
        $offer['offerKey'] = $this->offerKey;
        $offer['routeName'] = '';
        $offer['currencyNetto'] = $this->currencyNetto;
        $offer['amountNetto'] = $this->priceNetto;
        $offer['currencyBrutto'] = $this->currencyBrutto;
        $offer['amountBrutto'] = $this->priceBrutto;
        $offer['commissionCurrency'] = $this->commissionCurrency;
        $offer['commission'] = $this->commission;
        $offer['available'] = $this->available;
        $offer['lastTicketingDate'] = $this->lastTicketingDate;
        $offer['supplierCode'] = $this->supplierCode;
        $offer['flightTariff'] = $this->flightTariff;
        $offer['offerDateTime'] = (new DateTime())->format('Y-m-d H:i:s');
        $offer['fareType'] = $this->fareType;

        foreach ($this->route as $trip) {
            $offer['route'][] = $trip->toArray();
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


    /** NB! throw this >.< */

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
