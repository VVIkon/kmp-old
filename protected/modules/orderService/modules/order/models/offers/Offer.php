<?php

/**
 * Class Offer
 * Реализует функциональность управления
 * выбранным предложением услуги
 */
class Offer extends KFormModel implements IOffer
{
    const MATCH_COUNTRY = 1;
    const MATCH_CITY = 2;
    const MATCH_RESORT = 3;
    const MATCH_HOTEL = 4;
    const MATCH_MEAL = 5;
    const MATCH_TAG = 6;
    const MATCH_ROOM = 7;
    const MATCH_TOUR = 8;
    const MATCH_CURRENCY = 9;
    const MATCH_IMAGE = 10;

    /**
     * Идентифкатор предложения
     * @var int
     */
    public $offerId;

    /**
     * Сумма предложения
     * @var int
     */
    public $offerSumm;

    /**
     * Валюта предложения
     * @var int
     */
    public $currencyID;

    /** @var int ID поставщика по базе kt_ref_suppliers */
    public $supplierId;

    /**
     * Дата начала тура предложения
     * @var int
     */
    public $dateStart;

    /**
     * Поля для подробного описания предложения
     * @var
     */
    public $fields;

    /**
     * Детали предложения в формате УТК
     * @var
     */
    public $offerUtkDetails;

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules()
    {
        return array(
            array('offerId, offerSumm, currencyID, dateStart', 'safe'),
        );
    }

    public function setAttributes($params, $safeOnly = true)
    {
        parent::setAttributes($params, $safeOnly);
        return true;
    }

    /**
     * Получить идентификатор предложения
     * @return int
     */
    public function getOfferId()
    {
        return $this->offerId;
    }

    public function getOfferTouristsInfo()
    {

    }

    /**
     * Задать детали предложения
     * @param $params
     */
    public function setOfferDetails($params)
    {

    }

    /**
     * Получить детали предложения
     */
    public function getOfferDetails()
    {
        return [];
    }

    /**
     * Получить наименование предложения
     * @return string
     */
    public function getOfferName()
    {
        return '';
    }

    /**
     * Возврат ID поставщика
     */
    public function getSupplierId()
    {
        return $this->supplierId;
    }

    /**
     * Проверка структуры деталей оффера
     * на содержание требуемых полей
     * @param $details
     * @param $fields
     * @return bool
     */
    public function checkOfferDetails($details)
    {

        if (empty($this->fields) || count($this->fields) == 0) {
            return true;
        }

        foreach ($this->fields as $field) {
            if (!array_key_exists($field, $details)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Получить корректную дату из текстового параметра
     * @param $param
     * @param string $dateFormat
     * @return bool|DateTime
     */
    public function getDateParam($param, $dateFormat = 'Y-m-d\TH:i:s')
    {

        $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $param);
        if (empty($date) || $date->format('d.m.Y') == '01.01.0001') {
            return false;
        }

        return $date;
    }
}
