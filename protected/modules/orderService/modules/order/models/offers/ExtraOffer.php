<?php

/**
 * Class ExtraOffer
 * Реализует функциональность управления
 * выбранным предложением дополнительной услуги
 */
class ExtraOffer extends Offer implements IOffer
{
    /**
     * Идентифкатор предложения дополнительной услуги
     * @var int
     */
    public $extraId;

    /**
     * Описание дополительной услуги
     * @var
     */
    public $extraDescription;

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules() {

        $this->fields = ['extraDescription'];

        return array(
            array('extraDescription', 'safe'),
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

        $this->extraDescription = (empty($params['descriptionName'])) ? '' : $params['descriptionName'];

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
        $result = $this->extraDescription;
    }
 }

