<?php

/**
 * Class PacketOffer
 * Реализует функциональность управления
 * выбранным предложением пакета услуги
 */
class PacketOffer extends Offer implements IOffer
{
    /**
     * Идентифкатор предложения ЖД
     * @var int
     */
    public $railwayId;

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules() {

        $this->fields = [];
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

    public function saveOffer() {
        $this->offerId = mt_rand(1,200000);
        return true;
    }
 }

