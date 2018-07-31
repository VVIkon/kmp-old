<?php

/**
 * Class FlightSegment
 * Реализует функциональность для работы с данными
 * одного сегмента поездки из маршрута авиаперелёта
 */
class FlightSegment extends KFormModel
{
    /**
     * Идентифкатор предложения
     * @var string
     */
    private $offerKey;

    /**
     * Идентифкатор поездки из маршрута
     * @var
     */
    private $tripId;

    /**
     * Наименование сегмента поездки
     * @var
     */
    private $flightSegmentName;

    /**
     *
     * @var
     */
    private $validatingAirline;

    /**
     * Маркетинговая компания
     * @var
     */
    private $marketingAirline;

    /**
     * Оперирующая компания
     * @var
     */
    private $operatingAirline;

    /**
     * Компания перевозчик
     * @var
     */
    private $flightNumber;

    /**
     * Код воздушного судна
     * @var
     */
    private $aircraftCode;

    /**
     * Наименование воздушного судна
     * @var
     */
    private $aircraftName;

    /**
     * Тип класса авиаперелёта
     * @var
     */
    private $classType;

    /**
     * Код сегмента
     * @var
     */
    private $code;

    /**
     * Код аэропорта отправления
     * @var
     */
    private $departureAirportCode;

    /**
     * Дата и время отправления
     * @var
     */
    private $departureDate;

    /**
     * Терминал отправления
     * @var
     */
    private $departureTerminal;

    /**
     * Код аэропорта прибытия
     * @var
     */
    private $arrivalAirportCode;

    /**
     * Дата и время прибытия
     * @var
     */
    private $arrivalDate;

    /**
     * Терминал прибытия
     * @var
     */
    private $arrivalTerminal;

    /**
     * Код питания в самолёте
     * @var
     */
    private $mealCode;

    /**
     * Код допустимых размеров багажа
     * @var
     */
    private $baggageMeasureCode;

    /**
     * Код допустимого количества багажа
     * @var
     */
    private $baggageMeasureQuantity;

    private $baggageData;
    /**
     * Количество остановок
     * @var
     */
    private $stopQuantity;

    /**
     * Описание остановок
     * @var
     */
    private $stopLocations;

    /**
     * Количество свободных мест
     * @var
     */
    private $seatsAvailable;

    /**
     * Коды аэропортов остановок
     * @var
     */
    private $stopAirports;

    /**
     * Признак задержки рейса
     * @var int
     */
    private $delay;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($params)
    {
        $this->initParams($params);
    }

    /**
     * Инициализация параметров объектов
     * @param $params
     */
    public function initParams($params)
    {
        $this->offerKey = $params['offerKey'];
        $this->tripId = isset($params['tripId']) ? $params['tripId'] : '';

        $this->flightSegmentName = $params['flightSegmentName'];
        $this->validatingAirline = $params['validatingAirline'];
        $this->marketingAirline = $params['marketingAirline'];
        $this->operatingAirline = $params['operatingAirline'];
        $this->flightNumber = $params['flightNumber'];
        $this->aircraftCode = $params['aircraftCode'];
        $this->aircraftName = $params['aircraftName'];
        $this->classType = $params['categoryClassTypeName'];
        $this->code = $params['categoryClassTypeСode'];
        $this->departureAirportCode = $params['departureAirportCode'];
        $this->departureDate = $params['departureDate'];
        $this->departureTerminal = $params['departureTerminal'];
        $this->arrivalAirportCode = $params['arrivalAirportCode'];
        $this->arrivalDate = $params['arrivalDate'];
        $this->arrivalTerminal = $params['arrivalTerminal'];
        $this->mealCode = $params['mealCode'];
        $this->baggageMeasureCode = $params['baggageMeasureCode'];
        $this->baggageMeasureQuantity = $params['baggageMeasureQuantity'];
        $this->baggageData = $params['baggageData'];
        $this->stopQuantity = $params['stopQuantity'];
        $this->stopLocations = $params['stopLocations'];
        $this->seatsAvailable = $params['seatsAvailable'];
        $this->stopAirports = $params['stopAirports'];
        $this->delay = $params['delay'];

    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'tripId' :
                $this->tripId = $value;
                break;
        }
    }

    public function __get($name)
    {

        switch ($name) {
            case 'offerKey' :
                return $this->offerKey;
                break;
            case 'tripId' :
                return $this->tripId;
                break;
            case 'flightSegmentName' :
                return $this->flightSegmentName;
                break;
            case 'validatingAirline' :
                return $this->validatingAirline;
                break;
            case 'marketingAirline' :
                return $this->marketingAirline;
                break;
            case 'operatingAirline' :
                return $this->operatingAirline;
                break;
            case 'flightNumber' :
                return $this->flightNumber;
                break;
            case 'aircraftCode' :
                return $this->aircraftCode;
                break;
            case 'aircraftName' :
                return $this->aircraftName;
                break;
            case 'classType' :
                return $this->classType;
                break;
            case 'code' :
                return $this->code;
                break;
            case 'departureAirportCode' :
                return $this->departureAirportCode;
                break;
            case 'departureDate' :
                return $this->departureDate;
                break;
            case 'departureTerminal' :
                return $this->departureTerminal;
                break;
            case 'arrivalAirportCode' :
                return $this->arrivalAirportCode;
                break;
            case 'arrivalDate' :
                return $this->arrivalDate;
                break;
            case 'arrivalTerminal' :
                return $this->arrivalTerminal;
                break;
            case 'mealCode' :
                return $this->mealCode;
                break;
            case 'baggageMeasureCode' :
                return $this->baggageMeasureCode;
                break;
            case 'baggageMeasureQuantity' :
                return $this->baggageMeasureCode;
                break;
            case 'stopQuantity' :
                return $this->stopQuantity;
                break;
            case 'stopLocations' :
                return $this->stopQuantity;
                break;
            case 'seatsAvailable' :
                return $this->seatsAvailable;
                break;
            case 'stopAirport' :
                return $this->stopAirports;
                break;
            case 'delay' :
                return $this->delay;
                break;

        }
    }

    /**
     * Получение данных по сегментам перелёта из кэша
     * @param $offerKeys
     * @return array|CDbDataReader
     */
    public static function fromCache($offerKeys)
    {
        $command = Yii::app()->db->createCommand();

        $command->select(
            'offerKey, TripID tripId,flightSegmentName, validatingAirline, marketingAirline, operatingAirline,
                 flightNumber, aircraftCode, aircraftName, classType categoryClassTypeName,
                 code categoryClassTypeСode, departureAirportCode, departureDate, departureTerminal,
                 arrivalAirportCode, arrivalDate, arrivalTerminal, mealCode, baggageMeasureCode,
                 baggageMeasureQuantity, stopQuantity, stopLocations, seatsAvailable, stops_airport stopAirports, delay'
        );
        $command->from('fl_Segments');
        $command->where(['in', 'offerkey', $offerKeys]);
        $command->order('departureDate asc');

        try {
            $tripsInfo = $command->queryAll();
        } catch (Exception $e) {

            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_SEGMENT,
                $command->getText(),
                $e
            );
        }
        return $tripsInfo;
    }

    public function toCache($segmentNum)
    {
        $command = Yii::app()->db->createCommand();

        try {
            $res = $command->insert('fl_Segments', [
                'offerKey' => $this->offerKey,
                'TripID' => $this->tripId,
                'flightSegmentName' => $this->flightSegmentName,
                'validatingAirline' => $this->validatingAirline,
                'marketingAirline' => $this->marketingAirline,
                'operatingAirline' => $this->operatingAirline,
                'flightNumber' => $this->flightNumber,
                'aircraftCode' => $this->aircraftCode,
                'aircraftName' => $this->aircraftName,
                'classType' => $this->classType,
                'code' => $this->code,
                'departureAirportCode' => $this->departureAirportCode,
                'departureDate' => $this->departureDate,
                'departureTerminal' => $this->departureTerminal,
                'arrivalAirportCode' => $this->arrivalAirportCode,
                'arrivalDate' => $this->arrivalDate,
                'arrivalTerminal' => $this->arrivalTerminal,
                'mealCode' => $this->mealCode,
                'baggageMeasureCode' => $this->baggageMeasureCode,
                'baggageMeasureQuantity' => $this->baggageMeasureQuantity,
                'baggageData' => json_encode($this->baggageData),
                'stopQuantity'      => $this->stopQuantity,
                'stopLocations'     => json_encode($this->stopLocations, JSON_UNESCAPED_UNICODE),
                'seatsAvailable'    => $this->seatsAvailable,
                'stops_airport'     => $this->stopAirports,
                'delay'             => $this->delay,
                'segmentNum'        => $segmentNum
            ]);
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_CREATE_TRIP_SEGMENT,
                $command->getText(),
                $e
            );
        }

    }

    /**
     * Сравнение сегмента поездки с указанным сегментом поездки
     * @param $segment
     * @return bool
     */
    public function isEqual($segment)
    {
        if (empty($segment)) {
            return false;
        }

        if ($this->flightSegmentName != $segment->flightSegmentName
            || $this->validatingAirline != $segment->validatingAirline
            || $this->marketingAirline != $segment->marketingAirline
            || $this->operatingAirline != $segment->operatingAirline
            || $this->flightNumber != $segment->flightNumber
            || $this->aircraftCode != $segment->aircraftCode
            || $this->aircraftName != $segment->aircraftName
            || $this->classType != $segment->classType
            || $this->code != $segment->code
            || $this->departureAirportCode != $segment->departureAirportCode
            || $this->departureDate != $segment->departureDate
            || $this->departureTerminal != $segment->departureTerminal
            || $this->arrivalAirportCode != $segment->arrivalAirportCode
            || $this->arrivalDate != $segment->arrivalDate
            || $this->arrivalTerminal != $segment->arrivalTerminal
            || $this->mealCode != $segment->mealCode
            || $this->stopQuantity != $segment->stopQuantity
            || $this->stopAirports != $segment->stopAirports
        ) {
            return false;
        }

        return true;
    }

    /**
     * Вывод параметров объекта в массив
     * @param $lang язык вывода
     * @param $currency валюта вывода
     * @return array
     */
    public function toArray($lang, $currency)
    {
        $start = DateTime::createFromFormat('Y-m-d H:i:s', $this->departureDate)->getTimestamp();
        $end = DateTime::createFromFormat('Y-m-d H:i:s', $this->arrivalDate)->getTimestamp();
        $duration = ($end - $start) / 60;

        $stops = [];
        if (!empty($this->stopLocations)) {
            foreach ($this->stopLocations as $stopLocation) {

                /*$cityName = CitiesMapperHelper::getCityInfoBySupplierCityId(5,$stopLocation['cityId']);
                $countryName = CountryForm::getCountryInfoBySupplierCityId(5, $stopLocation['countryId']);*/

                $duration = DateTime::createFromFormat('Y-m-d H:i', $stopLocation['duration'])
                        ->format('H') * 60 + DateTime::createFromFormat('Y-m-d H:i', $stopLocation['duration'])
                        ->format('i');

                /*$stops[] = ['cityName' => $cityName, 'countryName' => $countryName ,'delay' => $duration];*/
                $stops[] = ['airport' => '', 'delay' => $duration];
            }
        }

        return [
            /*'flightSegmentName'  => $this->flightSegmentName,*/
            'supplierCodeSegment' => '',
            'validatingAirline' => $this->validatingAirline,
            'marketingAirline' => $this->marketingAirline,
            'operatingAirline' => $this->operatingAirline,
            'flightNumber' => $this->flightNumber,
            'aircraftCode' => $this->aircraftCode,
            'aircraftName' => $this->aircraftName,
            'categoryClass' => [
                'classType' => $this->classType,
                'code' => $this->code,
            ],
            'departureAirportCode' => $this->departureAirportCode,
            'departureAirportName' => isset($departureAirportInfo['airportName'])
                ? $departureAirportInfo['airportName']
                : '',
            'departureCityName' => isset($departureAirportInfo['cityName'])
                ? $departureAirportInfo['cityName']
                : '',
            'departureDate' => $this->departureDate,
            'departureTerminal' => $this->departureTerminal,
            'arrivalAirportCode' => $this->arrivalAirportCode,
            'arrivalAirportName' => isset($arrivalAirportInfo['airportName'])
                ? $arrivalAirportInfo['airportName']
                : '',
            'arrivalCityName' => isset($arrivalAirportInfo['cityName'])
                ? $arrivalAirportInfo['cityName']
                : '',
            'arrivalDate' => $this->arrivalDate,
            'arrivalTerminal' => $this->arrivalTerminal,
            'mealCode' => $this->mealCode,
            'baggageMeasureCode' => $this->baggageMeasureCode,
            'baggageMeasureQuantity' => $this->baggageMeasureQuantity,
            'stopQuantity' => $this->stopQuantity,
            'duration' => $duration,
            'stops' => $stops
            /*'seatsAvailable' => $this->seatsAvailable,*/
        ];
    }
}

