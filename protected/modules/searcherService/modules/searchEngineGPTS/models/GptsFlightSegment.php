<?php

/**
 * Class GptsFlightSegment
 * Реализует функциональность для работы с данными сегмента перелёта в GPTS
 */
class GptsFlightSegment extends KFormModel
{
    /**
     * Имя сегмента перелёта
     * @var string
     */
    private $flightSegmentName;

    /**
     * Код маркетинговой компании
     * @var string
     */
    private $marketingAirline;

    /**
     * Код операционной компании
     * @var string
     */
    private $operatingAirline;

    /**
     * Код валидирующей компании
     * @var string
     */
    private $validatingAirline;

    /**
     * Номер сегмента перелёта
     * @var string
     */
    private $flightNumber;

    /**
     * Код самолёта по классификатору IATA
     * @var string
     */
    private $aircraftCode;

    /**
     * Наименование самолёта
     * @var string
     */
    private $aircraftName;

    /**
     * Наименование класса перелёта
     * @var string
     */
    private $categoryClassTypeName;

    /**
     * Код класса перелёта
     * @var string
     */
    private $categoryClassTypeСode;

    /**
     * Код аэропорта отправления
     * @var string
     */
    private $departureAirportCode;

    /**
     * Дата и время отправления
     * @var string
     */
    private $departureDate;

    /**
     * Терминал отправления
     */
    private $departureTerminal;

    /**
     * Код аэропорта прибытия
     * @var string
     */
    private $arrivalAirportCode;

    /**
     * Дата и время прибытия
     * @var string
     */
    private $arrivalDate;

    /**
     * Терминал прибытия
     */
    private $arrivalTerminal;

    /**
     * Количество остановок
     * @var
     */
    private $stopQuantity;

    /**
     * Код питания
     * @var
     */
    private $mealCode;

    /**
     * Код требований к размеру багажа
     * @var string
     */
    private $baggageMeasureCode;

    /**
     * Код требований к количеству багажа
     * @var string
     */
    private $baggageMeasureQuantity;

    /**
     * Массив багажа по туристам
     * @var
     */
    private $baggageData;

    /**
     * Описание пунктов остановок
     * @var array
     */
    private $stopLocations;

    /**
     * Количество свободных мест для пассажиров
     * @var array
     */
    private $seatsAvailable;

    /**
     * Коды аэропортов остановок
     * @var array
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
     * Инициализация свойств сегмента маршрута перелёта
     * @param $params
     */
    public function initParams($params)
    {
        $baggageData = [];

        if (isset($params['baggageInfo']) && count($params['baggageInfo'])) {
            foreach ($params['baggageInfo'] as $baggageInfo) {
                $baggageData[] = [
                    'measureCode' => $baggageInfo['baggage']['unitCode'],
                    'measureQuantity' => $baggageInfo['baggage']['unitQuantity']
                ];
            }
        }

        $this->flightSegmentName = $params['flightSegmentName'];
        $this->marketingAirline = $params['marketingAirline'];
        $this->operatingAirline = $params['operatingAirline'];
        $this->validatingAirline = isset($params['validatingAirline']) ? $params['validatingAirline'] : '';
        $this->flightNumber = $params['flightNumber'];
        $this->aircraftCode = $params['aircraftCode'];
        $this->aircraftName = $params['aircraftName'];
        $this->categoryClassTypeName = $params['categoryClass']['classType'];
        $this->categoryClassTypeСode = $params['categoryClass']['code'];
        $this->departureAirportCode = $params['departureAirportCode'];
        $this->departureDate = $params['departureDate'];
        $this->departureTerminal = isset($params['departureTerminal'])
            ? $params['departureTerminal']
            : '';
        $this->arrivalAirportCode = $params['arrivalAirportCode'];
        $this->arrivalDate = $params['arrivalDate'];
        $this->arrivalTerminal = isset($params['arrivalTerminal'])
            ? $params['arrivalTerminal']
            : '';
        $this->stopQuantity = $params['stopQuantity'];

        $this->mealCode = isset($params['mealCode'])
            ? $params['mealCode']
            : '';
        $this->baggageMeasureCode = isset($params['baggageInfo'][0]['baggage']['unitCode']) ? $params['baggageInfo'][0]['baggage']['unitCode'] : '';
        $this->baggageMeasureQuantity = isset($params['baggageInfo'][0]['baggage']['unitQuantity']) ? $params['baggageInfo'][0]['baggage']['unitQuantity'] : '';
        $this->baggageData = $baggageData;
        $this->stopLocations = [];

        if(!empty($params['stopLocations'])) {
            foreach ($params['stopLocations'] as $stopLocation) {
                $cityId = CitiesMapperHelper::getCityIdBySupplierCityID(5, $stopLocation['cityId']);

                $city = CityRepository::getById($cityId);

                $this->stopLocations[] = [
                    'stopDuration' => DateTime::createFromFormat('H:i', $stopLocation['duration'])
                            ->format('H') * 60 + DateTime::createFromFormat('H:i', $stopLocation['duration'])
                            ->format('i'),
                    'stopCityName' => $city->getName()
                ];
            }
        }

        $this->seatsAvailable = '';
        $this->stopAirports = '';
        $this->delay = '';
    }

    public function __get($name)
    {

        switch ($name) {
            case 'flightSegmentName' :
                return $this->flightSegmentName;
                break;
            case 'marketingAirline' :
                return $this->marketingAirline;
                break;
            case 'operatingAirline' :
                return $this->operatingAirline;
                break;
            case 'validatingAirline' :
                return $this->validatingAirline;
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
            case 'categoryClassTypeName' :
                return $this->categoryClassTypeName;
                break;
            case 'categoryClassTypeСode' :
                return $this->categoryClassTypeСode;
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
            case 'stopQuantity' :
                return $this->stopQuantity;
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
            case 'stopLocations' :
                return $this->stopLocations;
                break;
            case 'seatsAvailable' :
                return $this->seatsAvailable;
                break;
            case 'stopAirports' :
                return $this->stopAirports;
                break;
            case 'delay' :
                return $this->delay;
                break;
        }

    }

    /**
     * Вывод параметров объекта в массив
     * @return array
     */
    public function toArray()
    {
        return [
            'flightSegmentName' => $this->flightSegmentName,
            'marketingAirline' => $this->marketingAirline,
            'operatingAirline' => $this->operatingAirline,
            'validatingAirline' => $this->validatingAirline,
            'flightNumber' => $this->flightNumber,
            'aircraftCode' => $this->aircraftCode,
            'aircraftName' => $this->aircraftName,
            'categoryClassTypeName' => $this->categoryClassTypeName,
            'categoryClassTypeСode' => $this->categoryClassTypeСode,
            'departureAirportCode' => $this->departureAirportCode,
            'departureDate' => $this->departureDate,
            'arrivalAirportCode' => $this->arrivalAirportCode,
            'arrivalDate' => $this->arrivalDate,
            'stopQuantity' => $this->stopQuantity,
            'departureTerminal' => $this->departureTerminal,
            'arrivalTerminal' => $this->arrivalTerminal,
            'mealCode' => $this->mealCode,
            'baggageMeasureCode' => $this->baggageMeasureCode,
            'baggageMeasureQuantity' => $this->baggageMeasureQuantity,
            'baggageData' => $this->baggageData,
            'stopLocations' => $this->stopLocations,
            'seatsAvailable' => $this->seatsAvailable,
            'stopAirports' => $this->stopAirports,
            'delay' => $this->delay
        ];
    }

}

