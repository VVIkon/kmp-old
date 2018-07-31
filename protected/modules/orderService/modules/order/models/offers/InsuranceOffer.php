<?php

/**
 * Class InsuranceOffer
 * Реализует функциональность управления
 * выбранным предложением услуги по страховки
 */
class InsuranceOffer extends Offer implements IOffer
{
    /**
     * Идентифкатор предложения по оформлению страховки
     * @var int
     */
    public $insuranceId;

    /**
     * Вид страховки
     * @var
     */
    public $insuranceType;

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules() {

        $this->fields = ['assuranceTypeName'];

        return array(
            array('accommodationId', 'safe'),
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

        $this->insuranceType = (empty($params['assuranceTypeName'])) ? '' : $params['assuranceTypeName'];

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
        return $this->insuranceType;
    }
 }

