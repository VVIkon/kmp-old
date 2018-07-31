<?php

/**
 * Class RailwayOffer
 * Реализует функциональность управления
 * выбранным предложением железнодорожной перевозки услуги
 */
class RailwayOffer extends Offer implements IOffer
{
    /**
     * Идентифкатор предложения ЖД
     * @var int
     */
    public $railwayId;

    /**
     * Номер рейса
     * @var string
     */
    public $tripNumber;

    /**
     * Маршрут
     * @var string
     */
    public $route;

    /**
     * Категория билета
     * @var string
     */
    public $railClass;

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules() {

        $this->fields = ['tripNumberName','railClassName','routeName'];

        return array(
            array('railwayId', 'safe'),
        );
    }

    
    /**
     * Установка атрибутов предложения
     * @param $params
     * @param bool|true $safeOnly
     */
    public function setAttributes($params, $safeOnly = true) {

        /*$offerParams = [];

        $offerParams['tourID'] = $this->findTour(1,$params['tourIdUTK']);
        $offerParams['nights'] = 8;
        $offerParams['adults'] = 3;
        $offerParams['children'] = 1;
        $offerParams['infants'] = '1';
        $offerParams['offerSumm'] = '2300.00';
        $offerParams['currencyID'] = '978';
        $offerParams['dateStart'] = '2015-01-22';

        parent::setAttributes($offerParams, $safeOnly);*/
    }

    /**
     * Установка деталей предложения
     * @param $params
     * @return bool
     */
    public function setOfferDetails($params) {

        if (empty($params)) {
            return false;
        }

        $this->tripNumber = (empty($params['tripNumberName'])) ? '' : $params['tripNumberName'];
        $this->railClass = (empty($params['railClassName'])) ? '' : $params['railClassName'];
        $this->route = (empty($params['routeName'])) ? '' : $params['routeName'];

        return true;
    }

    public function saveOffer() {
        $this->offerId = mt_rand(1,200000);
        return true;
    }

    /**
     * Получить название предложения
     * @return mixed
     */
    public function getOfferName() {
        $result = $this->tripNumber
        . (empty($this->route) ? '' : '; ' . $this->route)
        . (empty($this->railClass) ? '' : '; ' . $this->railClass);

        return preg_replace('/^;/','',$result);
    }
 }

