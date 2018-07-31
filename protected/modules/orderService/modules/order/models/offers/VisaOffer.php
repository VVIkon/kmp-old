<?php

/**
 * Class VisaOffer
 * Реализует функциональность управления
 * выбранным предложением услуги по офрмлению визы
 */
class VisaOffer extends Offer implements IOffer
{
    /**
     * Идентифкатор предложения оформления визы
     * @var int
     */
    public $visaId;

    /**
     * Тип визы
     * @var
     */
    public $visaType;

    /**
     * Кратность визы
     * @var
     */
    public $entryType;

    /**
     * Описание
     * @var
     */
    public $description;

    /**
     * Принятые документы
     * @var
     */
    public $validTill;

    /**
     * Дата сдано в посольство
     * @var
     */
    public $dateGive;

    /**
     * Получено из посольства
     * @var
     */
    public $dateTake;

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules() {

        $this->fields = ['visaTypeName','entryTypeName','descriptionName',
            'validTillName','dateGiveName','dateTakeName'];

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

        $this->visaType = (empty($params['visaTypeName'])) ? '' : $params['visaTypeName'];
        $this->entryType = (empty($params['entryTypeName'])) ? '' : $params['entryTypeName'];
        $this->description = (empty($params['descriptionName'])) ? '' : $params['descriptionName'];

        $validTill = $this->getDateParam($params['validTillName']);
        $dateGive = $this->getDateParam($params['dateGiveName']);
        $dateTake = $this->getDateParam($params['dateTakeName']);

        $this->validTill = (empty($validTill)) ? '' : $validTill->format('d.m.Y');
        $this->dateGive = (empty($dateGive)) ? '' : $dateGive->format('d.m.Y');
        $this->dateTake = (empty($dateTake)) ? '' : $dateTake->format('d.m.Y');

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
        $result = $this->entryType
        . (empty($this->visaType) ? '' : '; ' . $this->visaType)
        . (empty($this->description) ? '' : '; ' . $this->description)
        . (empty($this->validTill) ? '' : '; дейст. ' . $this->validTill)
        . (empty($this->dateGive) ? '' : '; отпр. ' . $this->dateGive)
        . (empty($this->dateTake) ? '' : ' выд. ' . $this->dateTake);

        return preg_replace('/^;/','',$result);
    }
 }

