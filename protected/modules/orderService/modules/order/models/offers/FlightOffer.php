<?php

/**
 * Class FlightOffer
 * Реализует функциональность управления
 * выбранным предложением перелёта услуги
 */
class FlightOffer extends Offer implements IOffer
{
    /**
     * Идентифкатор перелёта
     * @var int
     */
    public $flightID;

    /**
     * Наименование авиакомпании
     * @var string
     */
    public $airCompany;

    /**
     * Номер рейса
     * @var string
     */
    public $tripNumber;

    /**
     * Маршрут
     * @var string
     */
    public $route;

    /**
     * Категория билета
     * @var string
     */
    public $flightClass;

    /**
     * Дата и время туда
     * @var string
     */
    public $dateTo;

    /**
     * Дата и время обратно
     * @var string
     */
    public $dateFrom;

    /** @var mixed[] структура данных предложения */
    public $offerData;

    /** @var string Сгенерированное имя для названия услуги */
    public $serviceName;

    /** @var string дата начала оффера */
    private $startDate = 0;

    /** @var string дата окончания оффера */
    private $endDate = 0;

    /** @var string город услуги */
    private $targetCityId;

    /** @var string страна услуги */
    private $targetCountryId;

    /**
     * Задание правил валидации услуги
     * @return array
     */
    public function rules()
    {

        $this->fields = ['airCompanyName', 'tripNumberName',
            'routeName', 'flightClassName', 'dateToName', 'dateFromName'];

        return array(
            array('flightID,offerData', 'safe'),
        );
    }

    public function save()
    {
        $transaction = Yii::app()->db->beginTransaction();

        $command = Yii::app()->db->createCommand();

        $duration = '';

        $counter = 0;

        $CurrencyRates = CurrencyRates::getInstance();

        try {
            $res = $command->insert('kt_service_fl_Offer', [
                'offerKey' => $this->offerData['offerKey'],
                'routeName' => null,
                'currencyNetto' => $CurrencyRates->getIdByCode($this->offerData['price']['currency']),
                'amountNetto' => $this->offerData['price']['amountNet'],
                'currencyBrutto' => $CurrencyRates->getIdByCode($this->offerData['price']['saleCurrency']),
                'amountBrutto' => $this->offerData['price']['amountGross'],
                'available' => true,
                'lastTIcketingDate' => $this->offerData['lastTicketingDate'],
                'supplierCode' => $this->offerData['supplierCode'],
                'flightTariff' => $this->offerData['flightTariff'],
                'fareType' => null,
                'OfferDateTime' => '2016-01-20', //не знаю, откуда это и что это
                'adult' => $this->offerData['requestData']['adult'],
                'child' => $this->offerData['requestData']['children'],
                'infant' => $this->offerData['requestData']['infants'],
                'cancelAbility' => $this->offerData['cancelAbility'],
                'modifyAbility' => $this->offerData['modifyAbility']
            ]);

            $this->offerId = Yii::app()->db->lastInsertID;

            foreach ($this->offerData['itinerary'] as $trip) {
                $counter++;
                $duration = (int)$trip['duration'];

                $res = $command->insert('kt_service_fl_trip', [
                    'offerID' => $this->offerId,
                    'duration' => (int)$trip['duration']
                ]);

                $tripId = Yii::app()->db->lastInsertID;

                foreach ($trip['segments'] as $segment) {
                    $res = $command->insert('kt_service_fl_Segments', [
                        'offerID' => $this->offerId,
                        'TripID' => $tripId,
                        'flightSegmentName' => $segment['segment'],
                        'validatingAirline' => $segment['validatingAirline'],
                        'marketingAirline' => $segment['marketingAirline'],
                        'operatingAirline' => $segment['operatingAirline'],
                        'flightNumber' => $segment['flightNumber'],
                        'aircraftCode' => $segment['aircraft'], // @todo здесь надо разобраться, в ответе GPTS эти поля идентичны
                        'aircraftName' => $segment['aircraft'], // @todo плюс к тому, в структуре ответа getOffer их нет
                        'classType' => $segment['categoryClassType'],
                        'code' => null,
                        'departureAirportCode' => $segment['departureAirportCode'],
                        'departureDate' => $segment['departureDate'],
                        'departureTerminal' => $segment['departureTerminal'],
                        'arrivalAirportCode' => $segment['arrivalAirportCode'],
                        'arrivalDate' => $segment['arrivalDate'],
                        'arrivalTerminal' => $segment['arrivalTerminal'],
                        'mealCode' => $segment['mealCode'],
                        'baggageMeasureCode' => $segment['baggageMeasureCode'],
                        'baggageMeasureQuantity' => $segment['baggageMeasureQuantity'],
                        'stopQuantity' => $segment['stopQuantity'],
                        'stopLocations' => '', // @todo не реализовано, просто сериализацию?
                        'seatsAvailable' => null, // @todo не реализовано
                        'stops_airport' => null, // @todo не реализовано
                        'delay' => null, // @todo не реализовано
                        'supplierCodeSegment' => $segment['supplierCodeSegment'],
                        'duration' => $segment['duration'],
                        'segmentNum' => $segment['segmentNum']
                    ]);

                    /**
                     * определение начальной и конечной даты оффера
                     * @todo здесь должны быть еще учтены временные зоны
                     */
                    $segsd = strtotime($segment['departureDate']);

                    if ($this->startDate !== 0) {
                        if ($this->startDate > $segsd) {
                            $this->startDate = $segsd;
                        }
                    } else {
                        $this->startDate = $segsd;
                    }

                    $seged = strtotime($segment['arrivalDate']);

                    if ($this->endDate < $seged) {
                        $this->endDate = $seged;
                    }
                }

            }

            $this->supplierId = $this->offerData['supplierId'];
            $this->generateName();
            $this->setLocationData();

        } catch (Exception $e) {
            $transaction->rollback();/*
          throw new KmpDbException(
              get_class(),__FUNCTION__,
              OrdersErrors::CANNOT_CREATE_OFFER,
              $command->getText(),
              $e
          );
          */
            throw new KmpException(
                get_class(), __FUNCTION__,
                OrdersErrors::CANNOT_CREATE_OFFER,
                ['command' => $command->getText(), 'offerId' => $this->offerId, 'duration' => $duration, 'counter' => $counter]
            );
            return false;
        }

        $transaction->commit();

        return true;
    }

    /**
     * Инициализация предложения по его идентифкатору в БД
     * @param $offerId
     */
    public function load($offerId)
    {
        try {
            $command = Yii::app()->db->createCommand()
                ->select('*')
                ->from('kt_service_fl_Offer')
                ->where('offerID = :offerId', array(':offerId' => $offerId));

            $offerInfo = $command->queryRow();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::CANNOT_GET_OFFER,
                $command->getText(),
                $e
            );
        }

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_service_fl_trip')
            ->where('offerID = :offerId', array(':offerId' => $offerId));
        $tripsInfo = $command->queryAll();

        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_service_fl_Segments')
            ->where('offerID = :offerId', array(':offerId' => $offerId))
            ->order('segmentNum ASC');

        $segementsInfo = $command->queryAll();

        $currencyForm = CurrencyRates::getInstance();

        $supplier = SupplierStorage::getInstance()->getByIdOrCode($offerInfo['supplierCode']);

        $this->supplierId = $offerInfo['supplierCode'];
        $this->offerId = $offerInfo['offerID'];
        $this->offerData = [
            'requestData' => [
                'adult' => $offerInfo['adult'],
                'child' => $offerInfo['child'],
                'infant' => $offerInfo['infant']
            ],
            'supplierCode' => $supplier->getEngName(),
            'offerKey' => $offerInfo['offerKey'],
            'price' => [
                'currency' => $currencyForm->getCodeById($offerInfo['currencyNetto']),
                'amountNet' => $offerInfo['amountNetto'],
                'amountGross' => $currencyForm->calculateInCurrencyByIds($offerInfo['amountBrutto'], $offerInfo['currencyBrutto'], $offerInfo['currencyNetto']),
            ],
            'lastTicketingDate' => $offerInfo['lastTIcketingDate'],
            'flightTariff' => $offerInfo['flightTariff']
        ];

        $trips = [];
        foreach ($tripsInfo as $tripInfo) {
            $trip = [
                'routeName' => '', // @todo реализовать заполнение кодами городов
                'duration' => $tripInfo['duration'],
                'segments' => []
            ];

            foreach ($segementsInfo as $segmentInfo) {
                if ($segmentInfo['TripID'] == $tripInfo['TripId']) {

                    array_walk($segmentInfo, function (&$item, $key) {
                        $item = hashtableval($item, '');
                    });

                    $trip['segments'][] = [
                        'supplierCodeSegment' => $segmentInfo['supplierCodeSegment'],
                        'segment' => $segmentInfo['flightSegmentName'],
                        'validatingAirline' => $segmentInfo['validatingAirline'],
                        'marketingAirline' => $segmentInfo['marketingAirline'],
                        'operatingAirline' => $segmentInfo['operatingAirline'],
                        'flightNumber' => $segmentInfo['flightNumber'],
                        'aircraft' => $segmentInfo['aircraftName'],
                        'categoryClassType' => $segmentInfo['classType'],
                        'duration' => $segmentInfo['duration'],
                        'departureAirportCode' => $segmentInfo['departureAirportCode'],
                        'departureDate' => $segmentInfo['departureDate'],
                        'departureTerminal' => $segmentInfo['departureTerminal'],
                        'arrivalAirportCode' => $segmentInfo['arrivalAirportCode'],
                        'arrivalDate' => $segmentInfo['arrivalDate'],
                        'arrivalTerminal' => $segmentInfo['arrivalTerminal'],
                        'mealCode' => $segmentInfo['mealCode'],
//                        'baggageMeasureCode' => $segmentInfo['baggageMeasureCode'],
//                        'baggageMeasureQuantity' => $segmentInfo['baggageMeasureQuantity'],
                        'baggage' => json_decode($segmentInfo['baggageData'], true),
                        'stopQuantity' => $segmentInfo['stopQuantity'],
                        'stopLocations' => !empty($segmentInfo['stopLocations'])
                            ? unserialize($segmentInfo['stopLocations'])
                            : '',
                        'flightRules' => []
                    ];
                }
            }
            $trips[] = $trip;
        }

        $this->offerData['itinerary'] = $trips;
        return true;
    }

    /**
     * Установить наименование питания для каждого сегмента предложения
     * @param $langId
     */
    public function setSegmentsMealName($langId)
    {

        if (isset($this->offerData['itinerary'])) {

            $supplierCode = $this->offerData['supplierCode'];

            foreach ($this->offerData['itinerary'] as $tripKey => $trip) {

                foreach ($trip['segments'] as $segmentKey => $segment) {

                    $mealName = AirMealsForm::getMealNameByCode($supplierCode, $segment['mealCode'], $langId);

                    $this->offerData['itinerary'][$tripKey]['segments'][$segmentKey]['mealName'] =
                        $mealName ? $mealName : '';
                }
            }
        }
    }

    /**
     * Добавить поля наименований городов по указанным кодам IATA аэропортов
     * @param $langId
     */
    public function setSegmentsAirportCityName($langId)
    {
        if (isset($this->offerData['itinerary'])) {

            $supplierCode = $this->offerData['supplierCode'];

            foreach ($this->offerData['itinerary'] as $tripKey => $trip) {

                foreach ($trip['segments'] as $segmentKey => $segment) {

                    $iataCodes = [];

                    if (isset($segment['departureAirportCode'])) {
                        $iataCodes[] = $segment['departureAirportCode'];
                    }
                    if (isset($segment['arrivalAirportCode'])) {
                        $iataCodes[] = $segment['arrivalAirportCode'];
                    }

                    if ($langId == LangForm::LANG_EN) {
                        $airportsInfo = AirportsForm::getAirportsInfoByIataCodesEn($iataCodes);
                    } else {
                        $airportsInfo = AirportsForm::getAirportsInfoByIataCodesRu($iataCodes);
                    }

                    foreach ($airportsInfo as $airportInfo) {

                        if ($airportInfo['iata'] == $segment['departureAirportCode']) {
                            $this->offerData['itinerary'][$tripKey]['segments'][$segmentKey]['departureCityName'] =
                                $airportInfo['cityName'] ? $airportInfo['cityName'] : '';
                        }

                        if ($airportInfo['iata'] == $segment['arrivalAirportCode']) {
                            $this->offerData['itinerary'][$tripKey]['segments'][$segmentKey]['arrivalCityName'] =
                                $airportInfo['cityName'] ? $airportInfo['cityName'] : '';
                        }
                    }
                }
            }
        }
    }

    /**
     * Добавить поля наименований аэропортов по указанным кодам IATA аэропортов
     * @param $langId
     */
    public function setSegmentsAirportName($langId)
    {
        if (isset($this->offerData['itinerary'])) {

            $supplierCode = $this->offerData['supplierCode'];

            foreach ($this->offerData['itinerary'] as $tripKey => $trip) {

                foreach ($trip['segments'] as $segmentKey => $segment) {

                    $iataCodes = [];

                    if (isset($segment['departureAirportCode'])) {
                        $iataCodes[] = $segment['departureAirportCode'];
                    }

                    if (isset($segment['arrivalAirportCode'])) {
                        $iataCodes[] = $segment['arrivalAirportCode'];
                    }

                    if ($langId == LangForm::LANG_EN) {
                        $airportsInfo = AirportsForm::getAirportsInfoByIataCodesEn($iataCodes);
                    } else {
                        $airportsInfo = AirportsForm::getAirportsInfoByIataCodesRu($iataCodes);
                    }

                    foreach ($airportsInfo as $airportInfo) {

                        if ($airportInfo['iata'] == $segment['departureAirportCode']) {
                            $this->offerData['itinerary'][$tripKey]['segments'][$segmentKey]['departureAirportName'] =
                                $airportInfo['airportName'] ? $airportInfo['airportName'] : '';
                        }

                        if ($airportInfo['iata'] == $segment['arrivalAirportCode']) {
                            $this->offerData['itinerary'][$tripKey]['segments'][$segmentKey]['arrivalAirportName'] =
                                $airportInfo['airportName'] ? $airportInfo['airportName'] : '';
                        }
                    }
                }
            }
        }
    }

    /**
     * Установка стоимости предложения в указанной валюте
     * @param $currencyId
     */
    public function setPriceInCurrency($currencyId)
    {
        $CurrencyRates = CurrencyRates::getInstance();

        if (!$CurrencyRates->isCurrencyExists($currencyId)) {
            return false;
        }

        if (empty($this->offerData['price']) ||
            empty($this->offerData['price']['amountNet']) ||
            empty($this->offerData['price']['amountGross'])
        ) {
            return false;
        }

        $this->offerData['requestedPrice'] = [
            'currency' => $CurrencyRates->getCodeById($currencyId),
            'amountNet' => null,//$this->offerData['price']['amountNet'] / $CurrencyRates->getCurrencyRate($currencyId),
            'amountGross' => null//$this->offerData['price']['amountGross'] / $CurrencyRates->getCurrencyRate($currencyId)
        ];

        return true;
    }

    /**
     * Получить информацию о туристах предложения
     * @return bool|mixed
     */
    public function getOfferTouristsInfo()
    {
        if (empty($this->offerData) || empty($this->offerData['requestData'])) {
            return false;
        }

        return $this->offerData['requestData'];
    }

    /**
     * Возврат имени сервиса
     * @return string имя сервиса
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * Возврат даты начала услуги
     * @return string дата в формате MySQL
     */
    public function getStartDateTime()
    {
        return date('Y-m-d H:i:s', $this->startDate);
    }

    /**
     * Возврат ID города, характеризующего услугу
     * @return int ID города по справочнику KT
     */
    public function getCityId()
    {
        return $this->targetCityId;
    }

    /**
     * Возврат ID страны, характеризующей услугу
     * @return int ID страны по справочнику KT
     */
    public function getCountryId()
    {
        return $this->targetCountryId;
    }

    /**
     * Возврат даты окончания услуги
     * @return string дата в формате MySQL
     */
    public function getEndDateTime()
    {
        return date('Y-m-d H:i:s', $this->endDate);
    }

    /** Создание имени из параметров */
    private function generateName()
    {
        $nameparts = [];

        if (!empty($this->offerData['itinerary'])) {
            $nameparts[] = $this->offerData['itinerary'][0]['segments'][0]['departureAirportCode']
                . ' [' . $this->offerData['itinerary'][0]['segments'][0]['departureDate'] . '] ';

            foreach ($this->offerData['itinerary'] as $trip) {
                $nameparts[] = $trip['segments'][count($trip['segments']) - 1]['arrivalAirportCode']
                    . ' [' . $trip['segments'][count($trip['segments']) - 1]['arrivalDate'] . '] ';
            }
        }

        $this->serviceName = implode(' - ', $nameparts);
    }

    /**
     * Определение ID города и страны по справочнику KT, характеризующих услугу
     * @todo не стоит, наверное, полагаться на порядок следования сегментов, а вычислять исходя из segmentNum
     */
    private function setLocationData()
    {

        if (empty($this->offerData['itinerary'])) {
            return;
        }

        $firsttrip = $this->offerData['itinerary'][0];
        $lociata = $firsttrip['segments'][count($firsttrip['segments']) - 1]['arrivalAirportCode'];
        $locdata = AirportsForm::getAirportInfoByIataCodeEn($lociata);
        $this->targetCityId = $locdata['cityId'];
        $this->targetCountryId = $locdata['countryId'];
    }

    /**
     * Методы для работы с УТК
     * @deprecated
     */
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

        $this->airCompany = (empty($params['airCompanyName'])) ? '' : $params['airCompanyName'];
        $this->tripNumber = (empty($params['tripNumberName'])) ? '' : $params['tripNumberName'];
        $this->route = (empty($params['routeName'])) ? '' : $params['routeName'];
        $this->flightClass = (empty($params['flightClassName'])) ? '' : $params['flightClassName'];

        $dateTo = DateTime::createFromFormat('Y-m-d\TH:i:s', $params['dateToName']);
        $dateFrom = DateTime::createFromFormat('Y-m-d\TH:i:s', $params['dateFromName']);

        $this->dateTo = (empty($dateTo)) ? '' : $dateTo->format('d.m.Y');
        $this->dateFrom = (empty($dateFrom)) ? '' : $dateFrom->format('d.m.Y');

        return true;
    }

    public function getOfferDetails()
    {
        $details = [
            'airCompany_Id' => '',
            'airCompany_Name' => '',
            'tripNumber_Id' => '',
            'tripNumber_Name' => '',
            'route_Id' => '',
            'route_Name' => '',
            'ticketClass_Id' => '',
            'ticketClass_Name' => '',
            'dateTo_Id' => '',
            'dateTo_Name' => '',
            'dateFrom_Id' => '',
            'dateFrom_Name' => '',
        ];

        if (!empty($this->offerData['itinerary'])) {
            foreach ($this->offerData['itinerary'] as $tripKey => $trip) {
                foreach ($trip['segments'] as $segmentKey => $segment) {
                    $details['tripNumber_Name'] .= !empty($details['tripNumber_Name'])
                        ? '/' . $segment['operatingAirline'] . $segment['aircraft']
                        : $segment['operatingAirline'] . $segment['aircraft'];

                    $details['route_Name'] .= $segment['departureCityName'] . '-' . $segment['arrivalCityName'] . ';';

                    if ($tripKey == 0 && $segmentKey == 0) {
                        $DateTime = new DateTime($segment['departureDate']);

                        $details['dateTo_Name'] = $DateTime->format('Y-m-d\TH:i:s');
                    }

                    if ($tripKey == count($this->offerData['itinerary']) - 1
                        && $segmentKey == count($trip['segments']) - 1
                    ) {
                        $DateTime = new DateTime($segment['departureDate']);
                        $details['dateFrom_Name'] = $DateTime->format('Y-m-d\TH:i:s');
                    }
                }
            }
        }

        return $details;
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
        $result = $this->tripNumber
            . (empty($this->route) ? '' : '; ' . $this->route)
            . (empty($this->airCompany) ? '' : '; ' . $this->airCompany)
            . (empty($this->flightClass) ? '' : '; ' . $this->flightClass)
            . (empty($this->dateTo) ? '' : '; ' . $this->dateTo)
            . (empty($this->dateFrom) ? '' : '; ' . $this->dateFrom);

        return preg_replace('/^;/', '', $result);
    }
}
