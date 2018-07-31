<?php

/**
 * Class TransferOffer
 * Реализует функциональность управления
 * выбранным предложением услуги по доставке туриста
 */
class TransferOffer extends Offer implements IOffer
{
    /**
     * Идентифкатор предложения доставки туриста
     * @var int
     */
    public $transferId;

    /**
     * Идентификатор предложения
     */
    public $transferType;

    /**
     * Текстовое описание предложения
     * @var
     */
    public $description;

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules() {

        $this->fields = ['descriptionName','transferTypeId'];
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
     */
    public function setOfferDetails($params) {

        if (empty($params)) {
            return false;
        }

        $this->description = (empty($params['descriptionName'])) ? '' : $params['descriptionName'];
        $this->transferType = (empty($params['transferTypeId'])) ? '' : $params['transferTypeId'];
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
        return $this->description;
    }
 }

