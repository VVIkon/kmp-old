<?php

/**
 * Class FlightSearchRequest
 * Базовый класс для рботы с запросом поиска предложения по авиабилетам
 */
class FlightSearchRequest extends SearchRequest
{

    const ADD_OPERATION = 1;
    const OR_OPERATION = 2;
    const LIKE_OPERATION = 3;

    /** @var int ID пользователя, соверщающего запрос (KT) */
    public $agentId;

    /** @var int ID комании пользователя (KT) */
    private $clientId;

    /**
     * Поездки
     * @var array Trip
     */
    private $trips;

    /**
     * Тип маршрута
     * @var
     */
    private $tripType;

    /**
     * Дата начала поиска по расписанию
     * @var
     */
    private $scheduleDateFrom;

    /**
     * Дата окончания поиска по расписанию
     * @var
     */
    private $scheduleDateTo;

    /**
     * Класс полёта
     * @var
     */
    private $flightClass;

    /**
     * Признак поиска по чартерным рейсам
     * @var int
     */
    private $charter;

    /**
     * Признак поиска по регулярным рейсам
     * @var int
     */
    private $regular;

    /**
     * Число дней для гибкой даты
     * @var
     */
    private $flexibleDays;

    /**
     * Число взрослых пассажиров
     * @var int
     */
    private $adult;

    /**
     * Число пассажиров детского возраста
     * @var int
     */
    private $children;

    /**
     * Число пассажиров возраста до 2 лет
     * @var int
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
     * @param $type int тип поискового запроса
     */
    public function __construct($module, $type)
    {
        parent::__construct($module, $type);
        $this->namespace = $this->module->getConfig('log_namespace');

        $this->agentId = 0;
    }

    /**
     * Задать маршрут поискового запроса
     * @param $tripType
     * @param $trips
     * @return bool
     */
    public function setRoute($tripType, $trips)
    {
        $this->tripType = $tripType;

        $this->trips = [];
        foreach ($trips as $trip) {
            $this->trips[] = new Trip($trip['from'], $trip['to'], $trip['date']);
        }

        return true;
    }

    /**
     * Задать даты поиска по расписанию поискового запроса
     * @param $dateFrom
     * @param $dateTo
     * @return bool
     */
    public function setSchedule($dateFrom, $dateTo)
    {
        $this->scheduleDateFrom = $dateFrom;
        $this->scheduleDateTo = $dateTo;

        return true;
    }

    /**
     * Сохранить запрос в кэше запросов
     * @param $token
     * @return bool
     */
    public function toCache($token)
    {
        $command = Yii::app()->db->createCommand();
        $requestDateTime = new DateTime();
        try {
            $res = $command->insert('fl_searchRequest', [
                'token' => $token,
                'requestDateTime' => $requestDateTime->format('Y-m-d H:i:s'),
                'AgentID' => $this->clientId,
                'tripType' => $this->tripType,
                'flightClass' => $this->flightClass,
                'charter' => $this->charter,
                'regular' => $this->regular,
                'dateFromBySchedule' => $this->scheduleDateFrom,
                'dateToBySchedule' => $this->scheduleDateTo,
                'flexibleDays' => $this->flexibleDays,
                'adult' => $this->adult,
                'child' => $this->children,
                'infant' => $this->infants,
                'childAge' => implode(',', $this->childrenAges),
                'directFlight' => $this->directFlight,
                'flightNumber' => $this->flightNumber,
                'supplierCode' => $this->supplierCode,
                'airlineCode' => implode(',', $this->airlineCode),
                'uniteOffers' => $this->uniteOffers,
                'offerLimit' => $this->offerLimit
            ]);
        } catch (Exception $e) {

            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_CREATE_SEARCH_REQUEST,
                $command->getText() . ' clientId:' . $this->clientId . 'e',
                $e
            );
        }

        if (!$this->tripsToCache($token)) {
            return false;
        }

        return true;
    }

    /**
     * Сохранение маршрутов в БД
     * @param $token
     * @return bool
     */
    private function tripsToCache($token)
    {
        foreach ($this->trips as $trip) {

            $command = Yii::app()->db->createCommand();
            $requestDateTime = new DateTime();

            try {
                $res = $command->insert('fl_searchSegment', [
                    'token' => $token,
                    'from' => $trip->from,
                    'to' => $trip->to,
                    'date' => $trip->datestr,
                ]);

            } catch (Exception $e) {
                throw new KmpDbException(
                    get_class(),
                    __FUNCTION__,
                    SearcherErrors::CANNOT_CREATE_ROUTE_TRIP,
                    $command->getText(),
                    $e
                );
            }
        }
        return true;
    }

    /**
     * Поиск схожего по параметрам запроса
     * @param $params
     * @return array|bool
     */
    public function getSimilarRequestByParams($params)
    {
        $request = $this->makeSearchRequestText($params);

        if (!$request) {
            return false;
        }

        return $request;

    }

    /**
     * Формирование текста запроса для проверки существующего запроса в кэше
     * @param $params array наименования свойств по которым будет производиться поиск
     * @return bool|array
     */
    private function makeSearchRequestText($params)
    {
        if (empty($params) || count($params) == 0) {
            return false;
        }

        // вытащим время жизни токена
        $command = Yii::app()->db->createCommand();

        $command->select('*');
        $command->from('fl_searchRequest sr');
        $command = $this->addTokenLifetimeCondition($command);

        foreach ($params as $param) {

            switch ($param) {
                case 'clientId' :
                    if (!empty($this->clientId)) {
                        $command->andWhere('AgentID = :clientId', [':clientId' => $this->clientId]);
                    } else {
                        $command->andWhere('AgentID is null');
                    }
                    break;
                case 'route':

                    $searchWithDates = ($this->scheduleDateFrom && $this->scheduleDateTo) ? false : true;
                    $this->addRouteConditions($command, $searchWithDates);

                    if (!$searchWithDates) {
                        $command->andwhere("dateFromBySchedule = :from", [':from' => $this->scheduleDateFrom]);
                        $command->andwhere("dateToBySchedule = :to", [':to' => $this->scheduleDateTo]);
                    }
                    break;
                case 'flightClass' :
                    $command->andwhere('flightClass = :class', [':class' => $this->flightClass]);
                    break;
                case 'flexibleDays' :
                    $command->andwhere('flexibleDays = :flexibleDays', [':flexibleDays' => (bool)$this->flexibleDays]);
                    break;
                case 'regular' :
                    $command->andWhere('regular = :regular', [':regular' => $this->regular]);
                    break;
                case 'charter' :
                    $command->andWhere('charter = :charter', [':charter' => $this->charter]);
                    break;
                case 'adult' :
                    $command->andWhere('adult = :adult', [':adult' => $this->adult]);
                    break;
                case 'children' :
                    $command->andWhere('child = :child', [':child' => $this->children]);
                    break;
                case 'infants' :
                    $command->andWhere('infant = :infant', [':infant' => $this->infants]);
                    break;
                case 'childrenAges' :
                    if (empty($this->childrenAges)) {
                        $command->andWhere('childAge = "" or childAge is null');
                    } else {
                        $command->andWhere('childAge = :age', [':age' => implode(',', $this->childrenAges)]);
                    }
                    break;
                case 'directFlight' :
                    $command->andWhere('directFlight = :directFlight', [':directFlight' => (bool)$this->directFlight]);
                    break;
                /** @todo эта штука в GPTSне передается, а в базе почему-то лежит как 0 если его нет */
                case 'flightNumber' :
                    $command->andWhere('flightNumber = :flightNumber', [':flightNumber' => $this->flightNumber]);
                    break;
                case 'supplierCode' :
                    if (empty($this->supplierCode)) {
                        $command->andWhere('supplierCode = "" or supplierCode is null');
                    } else {
                        $command->andWhere('supplierCode = :supplierCode', [':supplierCode' => $this->supplierCode]);
                    }
                    break;
                case 'airlineCode' :
                    if (empty($this->airlineCode)) {
                        $command->andWhere('airlineCode = "" or airlineCode is null');
                    } else {
                        $command->andWhere('airlineCode = :airlineCode',
                            [':airlineCode' => implode(',', $this->airlineCode)]);
                    }
                    break;
                /*
                case 'uniteOffers' :
                  $command->andWhere('uniteOffers = :uniteOffers',[':uniteOffers' =>$this->uniteOffers]);
                  break;
                */
            }

        }
        return $command->queryRow();
    }

    /**
     * Добавить условия поиска по маршруту
     * @param $command
     * @param bool|true $withDates
     */
    private function addRouteConditions(&$command, $withDates = true)
    {
        $joinIndex = 1;
        $route = $this->getRouteFromTrips();

        foreach ($this->trips as $trip) {
            $alias = 'ss' . $joinIndex;
            $routeAlias = 'route' . $alias;
            if ($withDates) {

                $command->join(
                    "fl_searchSegment $alias",
                    "sr.token = $alias.token and $alias.from = '$trip->from'
                        and $alias.to = '$trip->to' and $alias.date = '$trip->datestr'"
                );

                $command->join(
                    "(SELECT token, GROUP_CONCAT(concat(`from`,'-', `to`)
                    ORDER BY id ASC SEPARATOR '-') route from fl_searchSegment group by token) as $routeAlias ",
                    "$alias.token = $routeAlias.token and $routeAlias.route = '$route'"
                );
            } else {
                $command->join(
                    "fl_searchSegment $alias",
                    "sr.token = $alias.token and $alias.from = '$trip->from' and $alias.to = '$trip->to'"
                );

                $command->join(
                    "(SELECT token, GROUP_CONCAT(concat(`from`,'-', `to`)
                    ORDER BY id ASC SEPARATOR '-') route from fl_searchSegment group by token) as $routeAlias ",
                    "$alias.token = $routeAlias.token and $routeAlias.route = '$route'"
                );
            }
            $joinIndex++;
        }
    }

    /**
     * Инициализация свойств поискового запроса из БД
     * @param $token
     * @return bool
     */
    public function loadFromCache($token)
    {
        $requestInfo = $this->getRequestFromDb($token);

        $this->clientId = $requestInfo['AgentID'];
        $this->flightClass = $requestInfo['flightClass'];
        $this->charter = $requestInfo['charter'];
        $this->regular = $requestInfo['regular'];
        $this->flexibleDays = $requestInfo['flexibleDays'];
        $this->adult = $requestInfo['adult'];
        $this->children = $requestInfo['child'];
        $this->infants = $requestInfo['infant'];
        $this->childrenAges = explode(',', $requestInfo['childAge']);
        $this->directFlight = $requestInfo['directFlight'];
        $this->flightNumber = $requestInfo['flightNumber'];
        $this->supplierCode = $requestInfo['supplierCode'];
        $this->airlineCode = explode(',', $requestInfo['airlineCode']);
        $this->uniteOffers = $requestInfo['uniteOffers'];
        $this->offerLimit = $requestInfo['offerLimit'];

        $this->setSchedule($requestInfo['dateFromBySchedule'], $requestInfo['dateToBySchedule']);
        $routeInfo = $this->getRequestRouteFromDb($token);

        $this->setRoute($requestInfo['tripType'], $routeInfo);
    }

    /**
     * Получить данные поискового запроса из БД
     * @param $token
     * @return bool
     */
    private function getRequestFromDb($token)
    {
        if (empty($token)) {
            return false;
        }

        $command = Yii::app()->db->createCommand();

        $command->select('*');
        $command->from('fl_searchRequest sr');
        $command->where('token = :token', [':token' => $token]);

        try {
            $requestParams = $command->queryRow();
        } catch (Exception $e) {

            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_SEARCH_REQUEST_PARAMS,
                $command->getText(),
                $e
            );
        }

        return $requestParams;
    }

    /**
     * Получить маршрут указанного поискового запроса
     * @param $token
     */
    private function getRequestRouteFromDb($token)
    {
        if (empty($token)) {
            return false;
        }

        $command = Yii::app()->db->createCommand();

        $command->select('*');
        $command->from('fl_searchSegment ss');
        $command->where('token = :token', [':token' => $token]);

        try {
            $routeParams = $command->queryAll();
        } catch (Exception $e) {

            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_GET_SEARCH_REQUEST_ROUTE_PARAMS,
                $command->getText(),
                $e
            );
        }

        return $routeParams;
    }

    /**
     * Установка свойств класса
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'clientId':
                $this->clientId = $value;
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
            case 'flexibleDays':
                $this->flexibleDays = $value;
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
            case 'clientId':
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
            case 'flexibleDays':
                return isset($this->flexibleDays);
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
                break;
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
            default :
                return false;
        }
    }

    /**
     * Получение строки наименований поездок маршрута
     * @return string
     */
    private function getRouteFromTrips()
    {
        $route = '';
        foreach ($this->trips as $trip) {

            $route .= empty($route) ? $trip->from . '-' . $trip->to : '-' . $trip->from . '-' . $trip->to;
        }
        return $route;
    }

    /**
     * Преобразование свойств объекта в массив
     * @return array
     */
    public function toArray()
    {
        $props = [
            'trips' => [],
            'providers' => [],
        ];

        foreach ($this->trips as $trip) {
            $props['trips'][] = $trip->toArray();
        }

        foreach ($this->providers as $provider) {
            $props['providers'][] = $provider;
        }

        $props = array_merge(
            $props,
            [
                'agentId' => $this->agentId,
                'clientId' => $this->clientId,
                'requestType' => $this->requestType,
                'flightClass' => $this->flightClass,
                'charter' => $this->charter,
                'regular' => $this->regular,
                'flexibleDays' => $this->flexibleDays,
                'adult' => $this->adult,
                'children' => $this->children,
                'infants' => $this->infants,
                'childrenAges' => $this->childrenAges,
                'directFlight' => $this->directFlight,
                'flightNumber' => $this->flightNumber,
                'supplierCode' => $this->supplierCode,
                'airlineCode' => $this->airlineCode,
                'uniteOffers' => $this->uniteOffers,
                'offerLimit' => $this->offerLimit,
            ]
        );

        return $props;
    }
}
