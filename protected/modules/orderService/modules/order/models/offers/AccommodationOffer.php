<?php

/**
 * Class AccommodationOffer
 * Реализует функциональность управления
 * выбранным предложением размещения услуги
 */
class AccommodationOffer extends Offer implements IOffer
{
    /**
     * Идентифкатор предложения размещения
     * @var int
     */
    public $accommodationId;

    /**
     * Страна размещения
     * @var string
     */
    public $country;

    /**
     * Город размещения
     * @var
     */
    public $city;

    /**
     * Наименование отеля
     * @var string
     */
    public $hotel;

    /**
     * Тип размещения
     * @var string
     */
    public $accommodationType;

    /**
     * Тип номера
     * @var string
     */
    public $roomType;

    /**
     * Тип питания
     * @var string
     */
    public $meal;

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules()
    {

        $this->fields = ['countryName', 'cityName', 'accomodationTypeName', 'hotelName', 'roomTypeName'];

        return array(
            array('accommodationId', 'safe'),
        );
    }

    /**
     *
     * @param $offerId
     * @return bool
     */
    public function load($offerId)
    {
        return true;
    }

    /**
     * Установка атрибутов предложения
     * @param $params
     * @param bool|true $safeOnly
     */
    public function setAttributes($params, $safeOnly = true)
    {

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
    public function setOfferDetails($params)
    {

        if (empty($params)) {
            return false;
        }

        $this->country = (empty($params['countryName']) ? '' : $params['countryName']);
        $this->city = (empty($params['cityName']) ? '' : $params['cityName']);
        $this->accommodationType = (empty($params['accomodationTypeName']) ? '' : $params['accomodationTypeName']);
        $this->hotel = (empty($params['hotelName']) ? '' : $params['hotelName']);
        $this->roomType = (empty($params['roomTypeName']) ? '' : $params['roomTypeName']);
        $this->meal = (empty($params['mealName']) ? '' : $params['mealName']);

        return true;
    }

    public function saveOffer()
    {
        $this->offerId = mt_rand(1, 200000);
        return true;
    }

    /**
     * Получить название предложения
     * @return mixed
     */
    public function getOfferName()
    {

        $result = $this->country
            . (empty($this->city) ? '' : '; ' . $this->city)
            . (empty($this->hotel) ? '' : '; ' . $this->hotel)
            . (empty($this->roomType) ? '' : '; ' . $this->roomType)
            . (empty($this->meal) ? '' : '; ' . $this->meal)
            . (empty($this->accommodationType) ? '' : '; ' . $this->accommodationType);

        return preg_replace('/^;/', '', $result);
    }

    public function getOfferDetails()
    {
//        $details = [
//            'airCompanyId' => '',
//            'airCompanyName' => '',
//            'tripNumberId' => '',
//            'tripNumberName' => '',
//            'routeId' => '',
//            'routeName' => '',
//            'ticketClassId' => '',
//            'ticketClassName' => '',
//            'dateToId' => '',
//            'dateToName' => '',
//            'dateFromId' => '',
//            'dateFromName' => ''
//        ];
//
//        if (!empty($this->offerData['itinerary'])) {
//            foreach ($this->offerData['itinerary'] as $tripKey => $trip) {
//
//                foreach ($trip['segments'] as $segmentKey => $segment) {
//
//                    $details['tripNumberName'] .= !empty($details['tripNumberName'])
//                        ? '/' . $segment['operatingAirline'] . $segment['aircraft']
//                        : $segment['operatingAirline'] . $segment['aircraft'];
//
//                    $details['routeName'] .= $segment['departureCityName'] . '-' . $segment['arrivalCityName'] . ';';
//
//                    if ($tripKey == 0 && $segmentKey == 0) {
//                        $details['dateToName'] = $segment['departureDate'];
//                    }
//
//                    if ($tripKey == count($this->offerData['itinerary']) - 1
//                        && $segmentKey == count($trip['segments']) - 1
//                    ) {
//                        $details['dateFromName'] = $segment['departureDate'];
//                    }
//                }
//            }
//        }
//
//        return $details;

        return [];
    }

}

