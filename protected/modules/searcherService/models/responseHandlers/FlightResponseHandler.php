<?php

/**
 * Class FlightResponseHandler
 * Класс для формирования возвращаемой информации по предложениям авиаперелёта
 */
class FlightResponseHandler extends ResponseHandler
{

    /**
     * Тип предложения в ответе от провайдера
     * @var
     */
    protected $offerType;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        parent::__construct($module);
    }

    public function __get($name)
    {
        switch ($name) {
            case 'offerType' :
                return $this->offerType;
                break;
        }
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }

    /**
     * Получить предложения авиаперелёта по указанному токену
     * @param $token string
     * @return FlightProviderOffer[]
     */
    public function getOffersByToken($token)
    {
        $offer = new FlightProviderOffer($this->module);

        $offersInfo = $offer->fromCache($token);

        $offersKeys = [];
        foreach ($offersInfo as $offerInfo) {
            if (!in_array($offerInfo['offerKey'], $offersKeys)) {
                $offersKeys[] = $offerInfo['offerKey'];
            }
        }
        $tripsInfo = FlightTrip::fromCache($offersKeys);
        $segmentsInfo = FlightSegment::fromCache($offersKeys);

        foreach ($tripsInfo as $tripsKey => $tripInfo) {

            foreach ($segmentsInfo as $segmentInfo) {
                if ($tripInfo['offerKey'] == $segmentInfo['offerKey'] &&
                    $tripInfo['tripId'] == $segmentInfo['tripId']
                ) {
                    $tripsInfo[$tripsKey]['segments'][] = $segmentInfo;
                }
            }
        }

        $offers = [];
        $offersAirports = [];

        foreach ($offersInfo as $offersKey => $offerInfo) {
            foreach ($tripsInfo as $tripsKey => $tripInfo) {
                if ($offerInfo['offerKey'] == $tripInfo['offerKey']) {
                    $offersInfo[$offersKey]['route'][] = $tripsInfo[$tripsKey];
                }
            }

            $offer = new FlightProviderOffer($this->module);
            $offer->initParams($offersInfo[$offersKey]);

            $iataCodes = $offer->getOfferAirportsIataCodes();

            foreach ($iataCodes as $iataCode) {
                if (!in_array($iataCode, $offersAirports)) {
                    $offersAirports[] = $iataCode;
                }
            }

            $offers[] = $offer;
        }

        return $offers;
    }

    /**
     * Сформировать информацию по предложениям авиаперелёт по указанным параметрам
     * @param $token
     * @param $lang
     * @param $currency
     * @return array
     */
    public function getOffersInfoByToken($token, $lang, $currency)
    {
        $offers = $this->getOffersByToken($token);

        if (empty($offers)) {
            return [];
        }

        $airportCodes = [];

        foreach ($offers as $offer) {

            $offerAirportsCodes = $offer->getOfferAirportsIataCodes();

            foreach ($offerAirportsCodes as $airportCode) {

                if (!in_array($airportCode, $airportCodes)) {
                    $airportCodes[] = $airportCode;
                }
            }
        }

        if ($lang == LangForm::LANG_EN) {
            $airportsInfo = AirportsForm::getAirportsInfoByIataCodesEn($airportCodes);
        } else {
            $airportsInfo = AirportsForm::getAirportsInfoByIataCodesRu($airportCodes);
        }

        $airportsDict = [];
        foreach ($airportsInfo as $airportInfo) {
            $airportsDict[] = [
                'iata' => $airportInfo['iata'],
                'airportName' => $airportInfo['airportName'],
                'cityName' => $airportInfo['cityName'],
                'countryName' => $airportInfo['countryName']
            ];
        }

        $response = [];
        $currencyForm = CurrencyRates::getInstance();

        foreach ($offers as $offer) {
            $offerInfo = $offer->toArray($lang, $currency, $currencyForm);

            if (empty($offerInfo['itinerary']) || count($offerInfo['itinerary']) == 0) {
                continue;
            }

            foreach ($offerInfo['itinerary'] as $tripKey => $trip) {

                if (empty($trip['segments']) || count($trip['segments']) == 0) {
                    continue;
                }

                foreach ($offerInfo['itinerary'][$tripKey]['segments'] as $segmentKey => $segment) {

                    foreach ($airportsDict as $airport) {

                        if ($airport['iata'] ==
                            $offerInfo['itinerary'][$tripKey]['segments'][$segmentKey]['departureAirportCode']
                        ) {
                            $offerInfo['itinerary'][$tripKey]['segments'][$segmentKey]['departureAirportName'] =
                                $airport['airportName'];
                            $offerInfo['itinerary'][$tripKey]['segments'][$segmentKey]['departureCityName'] =
                                $airport['cityName'];
                        }

                        if ($airport['iata'] ==
                            $offerInfo['itinerary'][$tripKey]['segments'][$segmentKey]['arrivalAirportCode']
                        ) {
                            $offerInfo['itinerary'][$tripKey]['segments'][$segmentKey]['arrivalAirportName'] =
                                $airport['airportName'];
                            $offerInfo['itinerary'][$tripKey]['segments'][$segmentKey]['arrivalCityName'] =
                                $airport['cityName'];
                        }
                    }

                }
            }

            $response[] = $offerInfo;
        }

        return $response;
    }
}
