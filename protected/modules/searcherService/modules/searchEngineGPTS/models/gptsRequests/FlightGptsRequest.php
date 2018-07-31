<?php

/**
 * Class FlightGptsRequest
 * Класс для работы с запросом поиска предложения по авиабилетам в GPTS
 */
class FlightGptsRequest extends GptsRequest
{

    /**
     * ID пользователя (GPTS), сделавшего запрос
     * @var int
     */
    private $agentIdGPTS;

    /**
     * ID агентства (GPTS) для поиска
     * @var int
     */
    private $clientIdGPTS;

    /**
     * Валюта, в которую необходимо конвертировать суммы в ответе
     * @var string
     */
    private $currency;

    /**
     * Класс полёта
     * @var
     */
    private $flightClass;

    /**
     * Признак поиска по чартерным рейсам
     * @var
     */
    private $charter;

    /**
     * Признак поиска по регулярным рейсам
     * @var
     */
    private $regular;

    /**
     * Признак гибкой даты
     * @var
     */
    private $flexibleSearch;

    /**
     * Поездки
     * @var array Trip
     */
    private $trips;

    /**
     * Число взрослых пассажиров
     * @var
     */
    private $adult;

    /**
     * Число пассажиров детского возраста
     * @var
     */
    private $children;

    /**
     * Число пассажиров возраста до 2 лет
     * @var
     */
    private $infants;

    /**
     * Набор возрастов детей
     * @var array
     */
    private $childrenAges;

    /**
     * Признак поиска перелётов без персадок
     * @var int
     */
    private $directFlight;

    /**
     * Номер рейса
     * @var string
     */
    private $flightNumber;

    /**
     * Код поставщика авиаперелёта
     * @var string
     */
    private $supplierCode;

    /**
     * IATA код авиакомпании
     * @var array[string]
     */
    private $airlineCode;

    /**
     * Признак объединения одинаковых найденных предложений
     * @var int
     */
    private $uniteOffers;

    /**
     * Максимальное количество выдаваемых предложений
     * @var
     */
    private $offerLimit;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module, $type)
    {
        parent::__construct($module, $type);
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Задать маршрут поискового запроса
     * @param $tripType
     * @param $trips
     * @return bool
     */
    public function setRoute($trips)
    {
        $this->trips = [];
        foreach ($trips as $trip) {
            $this->trips[] = new GptsTrip($trip['from'], $trip['to'], $trip['date']);
        }

        return true;
    }

    /**
     * Инициализация свойств поискового запроса
     * из другого поискового запроса
     * @param $request
     * @return bool
     */
    public function initFromRequest($params)
    {
        $this->setRoute($params['trips']);

        $clientIdGPTS = empty($params['clientId']) ? '' : ClientsHelper::getSupplierClientId($params['clientId'], ClientsHelper::GPTS);
        if (!$clientIdGPTS) {
            $clientIdGPTS = '';
        }

        $agentIdGPTS = (empty($params['agentId']) || (int)$params['agentId'] == 0) ? '' : UsersHelper::getSupplierUserId($params['agentId'], UsersHelper::GPTS);
        if (!$agentIdGPTS) {
            $agentIdGPTS = '';
        }

        $this->agentIdGPTS = $agentIdGPTS;
        $this->clientIdGPTS = $clientIdGPTS;
        $this->flightClass = (FlightClass::getNameById($params['flightClass']) != 'ANY')
            ? FlightClass::getNameById($params['flightClass'])
            : '';
        $this->charter = $params['charter'];
        $this->regular = $params['regular'];
        $this->flexibleSearch = ($params['flexibleDays'] > 0) ? true : false;
        $this->adult = $params['adult'];
        $this->children = $params['children'];
        $this->infants = $params['infants'];
        $this->childrenAges = $params['childrenAges'];
        $this->directFlight = $params['directFlight'];
        $this->flightNumber = $params['flightNumber'];

        if (!empty($params['supplierCode'])) {
            $supplierCodes = $this->module->getConfig('suppliers_gpts_codes');
            $this->supplierCode = (array_key_exists($params['supplierCode'], $supplierCodes))
                ? $supplierCodes[$params['supplierCode']]
                : '';
        } else {
            $this->supplierCode = '';
        }

        $this->airlineCode = $params['airlineCode'];
        $this->uniteOffers = $params['uniteOffers'];
        $this->offerLimit = ($params['offerLimit'] > 50) ? 0 : $params['offerLimit'];

        return true;
    }

    /**
     * Вывод свойств объекта в массив
     * @return array
     */
    public function toArray()
    {
        $props = [];
        foreach ($this->trips as $trip) {
            $props['trips'][] = $trip->toArray();
        }

        $props = array_merge(
            $props,
            [
                'requestType' => $this->requestType,
                'agentId' => $this->agentIdGPTS,
                'clientId' => $this->clientIdGPTS,
                'flightClass' => $this->flightClass,
                'charter' => $this->charter,
                'regular' => $this->regular,
                'flexibleSearch' => $this->flexibleSearch,
                'adult' => $this->adult,
                'children' => $this->children,
                'infants' => $this->infants,
                'childrenAges' => $this->childrenAges,
                'directFlight' => (bool)$this->directFlight,
                'flightNumber' => $this->flightNumber,
                'supplierCode' => $this->supplierCode,
                'airlineCode' => $this->airlineCode,
                'uniteOffers' => $this->uniteOffers,
                'offerLimit' => $this->offerLimit
            ]
        );

        return $props;
    }

    /**
     * Получить набор параметров для выполнения запроса к поставщику
     * @return array набор параметров
     */
    public function getRequestParams()
    {
        $trips = '';

        foreach ($this->trips as $trip) {
            $trips = !empty($trips)
                ? $trips . '&routes=' . urlencode($trip->toString())
                : urlencode($trip->toString());
        }

        $props['routes'] = $trips;

        foreach ($this->childrenAges as $age) {
            $ages = !empty($ages) ? $ages . '&childrenAges=' . $age : $age;
        }
        if (!empty($ages)) {
            $props['childrenAges'] = $ages;
        }

        foreach ($this->airlineCode as $airlineCode) {
            $airlineCodes = !empty($airlineCodes) ? $airlineCodes . '&airlineCodes=' . $airlineCode : $airlineCode;
        }
        if (!empty($airlineCodes)) {
            $props['airlineCodes'] = $airlineCodes;
        }

        $props = array_merge(
            $props,
            [
                'requestType' => $this->requestType,
                'clientId' => $this->clientIdGPTS,
                'agentId' => $this->agentIdGPTS,
                'flightClass' => $this->flightClass,
                'charter' => ($this->charter) ? "true" : "false",
                'regular' => ($this->regular) ? "true" : "false",
                'flexibleSearch' => $this->flexibleSearch,
                'adults' => $this->adult,
                'children' => $this->children,
                'infants' => $this->infants,

                'directFlight' => ($this->directFlight) ? "true" : "false",
                'flightNumber' => $this->flightNumber,
                'supplierId' => $this->supplierCode,
//                'airlineCodes'   => $this->airlineCode,
                'uniteOffers' => $this->uniteOffers,
                'offerLimit' => $this->offerLimit,
            ]
        );

        return $props;
    }

    /**
     * Установка свойств класса
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'agentIdGPTS':
                $this->agentIdGPTS = $value;
                break;
            case 'clientIdGPTS':
                $this->clientIdGPTS = $value;
                break;
            case 'flightClass':
                $this->flightClass = $value;
                break;
            case 'charter':
                $this->charter = $value;
                break;
            case 'regular':
                $this->regular = $value;
                break;
            case 'flexibleSearch':
                $this->flexibleSearch = $value;
                break;
            case 'adult':
                $this->adult = $value;
                break;
            case 'children':
                $this->children = $value;
                break;
            case 'infants':
                $this->infants = $value;
                break;
            case 'childrenAges':
                $this->childrenAges = $value;
                break;
            case 'directFlight':
                $this->directFlight = $value;
                break;
            case 'flightNumber':
                $this->flightNumber = $value;
                break;
            case 'supplierCode':
                $this->supplierCode = $value;
                break;
            case 'airlineCode':
                $this->airlineCode = $value;
                break;
            case 'uniteOffers':
                $this->uniteOffers = $value;
                break;
            case 'offerLimit':
                $this->offerLimit = $value;
                break;
            case 'currency':
                $this->currency = $value;
                break;
        }
    }

    /**
     * Проверка значений в свойствах класса
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        switch ($name) {
            case 'agentIdGPTS':
                return isset($this->agentId);
                break;
            case 'clientIdGPTS':
                return isset($this->clientId);
                break;
            case 'flightClass':
                return isset($this->flightClass);
                break;
            case 'charter':
                return isset($this->charter);
                break;
            case 'regular':
                return isset($this->regular);
                break;
            case 'flexibleSearch':
                return isset($this->flexibleSearch);
                break;
            case 'adult':
                return isset($this->adult);
                break;
            case 'children':
                return isset($this->children);
                break;
            case 'infants':
                return isset($this->infants);
                break;
            case 'childrenAges':
                return isset($this->childrenAges);
            case 'directFlight':
                return isset($this->directFlight);
                break;
            case 'flightNumber':
                return isset($this->flightNumber);
                break;
            case 'supplierCode':
                return isset($this->supplierCode);
                break;
            case 'airlineCode':
                return isset($this->airlineCode);
                break;
            case 'uniteOffers':
                return isset($this->uniteOffers);
                break;
            case 'offerLimit':
                return isset($this->offerLimit);
                break;
            case 'currency':
                return isset($this->currency);
                break;
            default :
                return false;
        }
    }
}
