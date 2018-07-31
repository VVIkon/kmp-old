<?php

/**
 * Class ToursOffer
 * Реализует функциональность управления
 * выбранным предложением тура услуги
 */
class ToursOffer extends Offer implements IOffer
{
    /**
     * Идентифкатор тура
     * @var int
     */
    public $tourID;

    /**
     * Количество ночей
     * @var int
     */
    public $nights;

    /**
     * Количество взрослых
     * @var int
     */
    public $adults;

    /**
     * Количество детей
     * @var int
     */
    public $children;

    /**
     * Количество младенцев
     * @var int
     */
    public $infants;

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules() {
        return array(
            array('offerId, offerSumm, currencyID, tourID, dateStart, nights, adults,
                    children, infants', 'safe'),
        );
    }

    public function findTour($supplierId, $serviceUtkTourId) {

        if (empty($serviceUtkTourId) || empty($supplierId)) {
            return null;
        }

        $command = Yii::app()
            ->db
            ->createCommand("select GetEntityID(:in_match_param, :in_supplier_id, :in_tour_id_utk);");

        $matchTour = self::MATCH_TOUR;
        $command->bindParam(":in_match_param", $matchTour, PDO::PARAM_STR);
        $command->bindParam(":in_supplier_id", $supplierId, PDO::PARAM_STR);
        $command->bindParam(":in_tour_id_utk", $serviceUtkTourId, PDO::PARAM_STR);

        return $command->queryRow();
    }

    public function setAttributes($params, $safeOnly = true) {

        $offerParams = [];

        $offerParams['tourID'] = $this->findTour(1,$params['tourIdUTK']);
        $offerParams['nights'] = 8;
        $offerParams['adults'] = 3;
        $offerParams['children'] = 1;
        $offerParams['infants'] = '1';
        $offerParams['offerSumm'] = '2300.00';
        $offerParams['currencyID'] = '978';
        $offerParams['dateStart'] = '2015-01-22';

        parent::setAttributes($offerParams, $safeOnly);
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

        $this->offerUtkDetails = [
            'tourType' => (empty($params['tourTypeName'])) ? '' : $params['tourTypeName'],
            'countryName' => (empty($params['countryName'])) ? '' : $params['countryName'],
            'cityName' => (empty($params['cityName'])) ? '' : $params['cityName'],
            'resort' => (empty($params['resortName'])) ? '' : $params['resortName'],
            'hotel' => (empty($params['hotelName'])) ? '' : $params['hotelName'],
            'hotelCategory' => (empty($params['hotelCategoryName'])) ? '' : $params['hotelCategoryName'],
            'roomCategory' => (empty($params['roomCategoryName'])) ? '' : $params['roomCategoryName'],
            'roomType' => (empty($params['roomTypeName'])) ? '' : $params['roomTypeName'],
            'meal' => (empty($params['mealName'])) ? '' : $params['mealName'],
            'weeks' => (empty($params['weeksName'])) ? '' : $params['weeksName'],
            'accomodation' => (empty($params['accomodationName'])) ? '' : $params['accomodationName'],
            'people' => (empty($params['peopleName'])) ? '' : $params['peopleName'],
            'insurance' => (empty($params['insuranceName'])) ? '' : $params['insuranceName'],
            'transfer' => (empty($params['transferName'])) ? '' : $params['transferName'],
            'excursion' => (empty($params['excursionName'])) ? '' : $params['excursionName'],
            'treatment' => (empty($params['treatmentName'])) ? '' : $params['treatmentName'],
            'airCompany' => (empty($params['airCompanyName'])) ? '' : $params['airCompanyName'],
            'airTicket' => (empty($params['airTicketName'])) ? '' : $params['airTicketName'],
        ];

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

        $result = $this->offerUtkDetails['tourType'] 
            . (empty($this->offerUtkDetails['countryName']) ? '' : '; ' . $this->offerUtkDetails['countryName'])
            . (empty($this->offerUtkDetails['cityName']) ? '' : '; ' . $this->offerUtkDetails['cityName'])
            . (empty($this->offerUtkDetails['resort']) ? '' : '; ' . $this->offerUtkDetails['resort'])
            . (empty($this->offerUtkDetails['hotel']) ? '' : '; ' . $this->offerUtkDetails['hotel'])
            . (empty($this->offerUtkDetails['hotelCategory']) ? '' : '; ' . $this->offerUtkDetails['hotelCategory'])
            . (empty($this->offerUtkDetails['roomCategory']) ? '' : '; ' . $this->offerUtkDetails['roomCategory'])
            . (empty($this->offerUtkDetails['roomType']) ? '' : '; ' . $this->offerUtkDetails['roomType'])
            . (empty($this->offerUtkDetails['meal']) ? '' : '; ' . $this->offerUtkDetails['meal']);

        return preg_replace('/^;/','',$result);
    }
 }

