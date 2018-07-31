<?php

/**
 * Class CarRentOffer
 * Реализует функциональность управления
 * выбранным предложением услуги - аренда машины
 */
class CarRentOffer extends Offer implements IOffer
{
    /**
     * Идентификатор аренды
     * @var int
     */
    public $rentId;

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

        $this->fields = ['descriptionName'];

        return array(
            array('rentId', 'safe'),
        );
    }

    public function setAttributes($params, $safeOnly = true) {
        $offerParams = [];

        parent::setAttributes($offerParams, $safeOnly);
    }

    public function saveOffer() {
        $this->offerId = mt_rand(1,200000);
        return true;
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

