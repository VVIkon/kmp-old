<?php

/**
 * Class AccomodationSearchRequest
 * Класс для работы с запросом поиска предложения по размещению
 */
class AccomodationSearchRequest extends SearchRequest
{
    const ADD_OPERATION = 1;
    const OR_OPERATION = 2;
    const LIKE_OPERATION = 3;

    /** @var array  Номера размещения */
    public $rooms = [];
    /** @var string Код поставщика */
    public $supplierCode;
    /** @var int Идентифкатор компании клиента в КТ */
    public $clientId;
    /** @var int Идентифкатор пользователя в КТ */
    public $agentId;
    /** @var int Идентификатор города */
    public $cityId;
    /** @var string Дата заезда в отель */
    public $dateFrom;
    /** @var string Дата выезда из отеля */
    public $dateTo;
    /** @var bool Признак поиска только свободные номера */
    public $freeOnly;
    /** @var int Количество дней для поиска по гибким датам(+/- дней) */
    public $flexibleDays;
    /** @var int Идентификатор отеля в КТ */
    public $hotelId;
    /** @var string Идентификатор отеля по поставщику */
    public $hotelCode;
    /** @var string Наименование поставщика отеля */
    public $hotelSupplier;
    /** @var int Категория отеля */
    public $category;
    /** @var array Список ID отельных сетей */
    public $hotelChains;
    /** @var array Массив кодов типа питания */
    public $mealType;

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
     * Установка параметров номеров проживания
     * @param $rooms
     */
    public function setRooms($roomsInfo)
    {
        if (empty($roomsInfo)) {
            return false;
        }

        foreach ($roomsInfo as $roomInfo) {
            $room = new Room(
                $roomInfo['adult'],
                Room::getChildrenCount($roomInfo['childrenAges']),
                Room::getInfantsCount($roomInfo['childrenAges'])
            );

            $this->rooms[] = $room;
        }
    }

    /**
     * Получить информацию о номерах в виде массива
     * @return array
     */
    public function roomsToArray()
    {
        $roomsInfo = [];

        if (empty($this->rooms)) {
            return $roomsInfo;
        }

        foreach ($this->rooms as $room) {
            $roomsInfo[] = $room->toArray();
        }

        return $roomsInfo;
    }

    /**
     * Получить массив возрастов детей
     * @return array
     */
    /* public function getChildrenAges()
     {
         $ages = [];
         if (empty($this->rooms)) {
             return [];
         }

         foreach ($this->rooms as $room) {
             $ages = array_merge($ages,$room->childrenToArray());
         }

         return $ages;
     }*/

    /**
     * Получить количество взрослых из информации о комнатах
     * @return int
     */
    /*public function getAdults()
    {
        $adults = 0;

        if (empty($this->rooms)) {
            return $adults;
        }

        foreach ($this->rooms as $room) {
            $adults += $room->adults;
        }
        return $adults;
    }*/

    /**
     * Сохранить запрос в кэше запросов
     * @param $token
     * @return bool
     */
    public function toCache($token)
    {

        $requestDateTime = new DateTime();
        $mealTypes = null;
        if (isset($this->mealType) && is_array($this->mealType)) {
            sort($this->mealType);
            $mealTypes = implode('||', $this->mealType);
        }

        try {

            $res = Yii::app()->db->createCommand()->insert('ho_searchRequest', [
                'token' => $token,
                'requestDateTime' => $requestDateTime->format('Y-m-d H:i:s'),
                'agentId' => (!empty($this->clientId) ? $this->clientId : null),
                'cityId' => $this->cityId,
                'hotelId' => $this->hotelId,
                'SupplierCode' => $this->supplierCode,
                'hotelCode' => $this->hotelCode,
                'category' => (!empty($this->category) ? $this->category : null),
                'hotelChains' => (isset($this->hotelChains) && is_array($this->hotelChains)
                    ? implode('|', $this->hotelChains)
                    : null),
                'hotelSupplier' => $this->hotelSupplier,
                'dateFrom' => $this->dateFrom,
                'dateTo' => $this->dateTo,
                'mealType' => $mealTypes,
                'freeOnly' => $this->freeOnly,
                'flexibleDays' => $this->flexibleDays,
                'room' => json_encode($this->rooms, JSON_NUMERIC_CHECK)
            ]);
        } catch (Exception $e) {

            throw new KmpDbException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::CANNOT_CREATE_SEARCH_REQUEST,
                $command->getText(),
                $e
            );
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

        $command = Yii::app()->db->createCommand();

        $command->select('*');
        $command->from('ho_searchRequest sr');

        $command = $this->addTokenLifetimeCondition($command);

        foreach ($params as $param) {
            switch ($param) {
                case 'clientId':
                    if (empty($this->clientId)) {
                        $command->andwhere('agentId is null');
                    } else {
                        $command->andwhere('agentId = :clientId', [':clientId' => $this->clientId]);
                    }
                    break;
                case 'cityId':
                    $command->andwhere('cityId = :cityId', [':cityId' => $this->cityId]);
                    break;
                case 'dateTo':
                    $command->andwhere('dateTo = :dateTo', [':dateTo' => $this->dateTo]);
                    break;
                case 'dateFrom':
                    $command->andwhere('dateFrom = :dateFrom', [':dateFrom' => $this->dateFrom]);
                    break;
                case 'category':
                    if (empty($this->category)) {
                        $command->andwhere('category is null');
                    } else {
                        $command->andWhere('category = :category', [':category' => $this->category]);
                    }
                    break;
                case 'hotelChains':
                    if (isset($this->hotelChains) && is_array($this->hotelChains)) {
                        $command->andWhere('hotelChains = :hotelChains', [':hotelChains' => implode('|', $this->hotelChains)]);
                    } else {
                        $command->andWhere('hotelChains is null');
                    }
                    break;
                case 'hotelCode':
                    if (empty($this->hotelCode)) {
                        $command->andWhere('hotelCode = "" or hotelCode is null');
                    } else {
                        $command->andWhere('hotelCode = :hotelCode', [':hotelCode' => $this->hotelCode]);
                    }
                    break;
                // todo добавить или удалить после поиска предложений
                case 'hotelSupplier':
                    if (empty($this->hotelSupplier)) {
                        $command->andWhere('hotelSupplier = "" or hotelSupplier is null');
                    } else {
                        $command->andWhere('hotelSupplier = :hotelSupplier', [':hotelSupplier' => $this->hotelSupplier]);
                    }
                    break;
                case 'mealType':
                    if (isset($this->mealType) && is_array($this->mealType)) {
                        sort($this->mealType);
                        $mealTypes = implode('||', $this->mealType);
                        $command->andWhere('mealType = :mealTypes', [':mealTypes' => $mealTypes]);
                    } else {
                        $command->andWhere('mealType is null');
                    }
                    break;
                case 'flexibleDays':
                    $command->andWhere('flexibleDays = :flexibleDays', [':flexibleDays' => (bool)$this->flexibleDays]);
                    break;
                case 'freeOnly':
                    $command->andWhere('freeOnly = :freeOnly', [':freeOnly' => (bool)$this->freeOnly]);
                    break;
                case 'rooms':
                    $command->andWhere('room = :rooms', [':rooms' => json_encode($this->rooms, JSON_NUMERIC_CHECK)]);
                    break;
            }
        }

//        var_dump($command->queryRow());
//        exit;

        return $command->queryRow();
    }

    /**
     * Инициализация свойств поискового запроса из БД
     * @param $token
     * @return bool
     */
    public function loadFromCache($token)
    {
        $requestInfo = $this->getRequestFromDb($token);
        $this->clientId = $requestInfo['agentId'];
        $this->cityId = $requestInfo['cityId'];
        $this->hotelCode = $requestInfo['hotelCode'];
        $this->hotelSupplier = $requestInfo['hotelSupplier'];
        $this->hotelId = $requestInfo['hotelId'];
        $this->supplierCode = $requestInfo['SupplierCode'];
        $this->category = $requestInfo['category'];
        $this->hotelChains = !is_null($requestInfo['hotelChains'])
            ? explode('|', $requestInfo['hotelChains'])
            : null;
        $this->dateFrom = $requestInfo['dateFrom'];
        $this->dateTo = $requestInfo['dateTo'];
        $this->freeOnly = $requestInfo['freeOnly'];
        $this->flexibleDays = $requestInfo['flexibleDays'];
        $this->rooms = !is_null($requestInfo['room'])
            ? json_decode($requestInfo['room'], true)
            : [];
        $this->mealType = !is_null($requestInfo['mealType'])
            ? explode('||', $requestInfo['mealType'])
            : null;
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
        $command->from('ho_searchRequest sr');
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
     * Преобразование свойств объекта в массив
     * @return array
     */
    public function toArray()
    {
        return [
            'requestType' => $this->requestType,
            'supplierCode' => $this->supplierCode,
            'clientId' => $this->clientId,
            'agentId' => $this->agentId,
            'cityId' => $this->cityId,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'freeOnly' => $this->freeOnly,
            'flexibleDays' => $this->flexibleDays,
            'hotelCode' => $this->hotelCode,
            'hotelSupplier' => $this->hotelSupplier,
            'hotelId' => $this->hotelId,
            'hotelName' => $this->hotelName,
            'category' => $this->category,
            'hotelChains' => $this->hotelChains,
            'mealType' => $this->mealType,
            'rooms' => $this->rooms
        ];
    }


}
