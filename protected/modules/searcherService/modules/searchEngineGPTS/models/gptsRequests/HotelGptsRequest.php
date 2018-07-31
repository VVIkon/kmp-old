<?php

/**
 * Class HotelGptsRequest
 * Класс для работы с запросом поиска предложений размещения в GPTS
 */
class HotelGptsRequest extends GptsRequest
{
    /** @var string Идентифкатор компании клиента (GPTS) */
    private $clientIdGPTS;
    /** @var int ID пользователя (GPTS), сделавшего запрос */
    private $agentIdGPTS;
    /** @var string Код поставщика (по версии GPTS) */
    private $supplierId;
    /** @var string Валюта, в которую необходимо конвертировать суммы в ответе */
    private $currency;
    /** @var int Идентификатор города */
    private $cityId;
    /** @var string Код отеля */
    private $hotelCode;
    /** @var string Дата заезда */
    private $startDate;
    /** @var string Дата выезда */
    private $endDate;
    /** @var bool Признак поиска свободных номеров */
    private $freeOnly;
    /** @var bool Признак гибкой даты */
    private $flexibleSearch;
    /** @var array массив с данными состава туристов на каждый искомый номер */
    private $rooms;
    /** @var int|null категория отеля */
    private $category;
    /** @var array массив типа питания */
    private $mealType;
    /** @var array массив кодов отельных цепей */
    private $hotelChains;
    /** @var array Количество взрослых */
    //private $adultsCount;
    /** @var array Массив возрастов детей */
    //private $childrenAges;

    /**
     * @param $module object
     */
    public function __construct($module, $type)
    {
        parent::__construct($module, $type);
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Инициализация свойств поискового запроса
     * из другого поискового запроса
     * @param $request
     * @return bool
     */
    public function initFromRequest($params)
    {
        $clientIdGPTS = empty($params['clientId']) ? '' : ClientsHelper::getSupplierClientId($params['clientId'], ClientsHelper::GPTS);
        $this->clientIdGPTS = !empty($clientIdGPTS) ? $clientIdGPTS : '';

        $agentIdGPTS = (empty($params['agentId']) || (int)$params['agentId'] == 0) ? '' : UsersHelper::getSupplierUserId($params['agentId'], UsersHelper::GPTS);
        $this->agentIdGPTS = !empty($agentIdGPTS) ? $agentIdGPTS : '';

        $supplierId = $this->getGPTSSupplierId($params['supplierCode']);
        if ($supplierId !== false) {
            $this->supplierId = $supplierId;
        } else {
            return false;
        }

        $this->mealType = isset($params['mealType']) ? $params['mealType'] : null;
        $this->cityId = CitiesMapperHelper::getProviderCityId($params['cityId'], ProvidersFactory::GPTS_PROVIDER_ID);
        $this->hotelCode = isset($params['hotelCode']) ? $params['hotelCode'] : null;
        $this->startDate = $params['dateFrom'];
        $this->endDate = $params['dateTo'];
        $this->freeOnly = $params['freeOnly'] == 1 ? 'true' : 'false';
        $this->flexibleSearch = $params['flexibleDays'] == 1 ? 'true' : 'false';

        //$this->adultsCount = 1;
        //$this->childrenAges = [];

        if (is_array($params['rooms'])) {
            $this->rooms = $params['rooms'];
        } else {
            /** @todo вообще это должно валидироваться */
            return false;
        }

        $this->category = isset($params['category']) ? $params['category'] : null;
        $this->hotelChains = null;
        if (is_array($params['hotelChains'])) {
            $hotelChains = Yii::app()->db->createCommand()
                ->select('hotelChainCode')
                ->from('ho_ref_hotelChain')
                ->where(['in', 'idHotelChain', $params['hotelChains']])
                ->queryColumn();
            if (count($hotelChains) > 0) {
                $this->hotelChains = $hotelChains;
            }
        }

        return true;
    }

    /**
     * Получение кода поставщика по ID KT
     * @todo должно быть вынесено в класс для работы с поставщиками?
     * @param int $supplierId ID поставщика по версии КТ
     * @return int ID поставщика по версии GPTS
     */
    private function getGPTSSupplierId($supplierId)
    {
        $command = Yii::app()->db->createCommand();

        $command->select('SupplierID_GPTS')
            ->from('kt_ref_suppliers')
            ->where('SupplierID = :supplierId', [':supplierId' => $supplierId]);

        return $command->queryScalar();
    }

    /**
     * Вывод свойств объекта в массив
     * @return array
     */
    public function toArray()
    {
        /*$props = [];
        foreach ($this->trips as $trip) {
            $props['trips'][] = $trip->toArray();
        }

        $props = array_merge(
            $props,
            [
                'requestType'   => $this->requestType,
                'flightClass'   => $this->flightClass,
                'charter'       => $this->charter,
                'regular'       => $this->regular,
                'flexibleSearch'  => $this->flexibleSearch,
                'adult'         => $this->adult,
                'children'      => $this->children,
                'infants'       => $this->infants,
                'childrenAges'  => $this->childrenAges,
                'directFlight'  => $this->directFlight,
                'flightNumber'  => $this->flightNumber,
                'supplierCode'  => $this->supplierCode,
                'airlineCode'   => $this->airlineCode,
                'uniteOffers'   => $this->uniteOffers,
                'offerLimit'    => $this->offerLimit
            ]
        );

        return $props;*/
    }

    /**
     * Получить набор параметров для выполнения запроса к поставщику
     * @return array набор параметров
     */
    public function getRequestParams()
    {
        $props = [
            'supplierId' => $this->supplierId,
            'requestType' => $this->requestType,
            'clientId' => $this->clientIdGPTS,
            'agentId' => $this->agentIdGPTS,
//            'convertToCurrency' => $this->currency,
            'convertToCurrency' => 'ORIGINAL',
            'cityId' => $this->cityId,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'freeOnly' => $this->freeOnly,
            'flexibleDates' => $this->flexibleSearch,
        ];

        $gprooms = array_map(function($room) {
            $gproom = 'adults:' . $room['adults'];
            if (count($room['childrenAges']) > 0) {
                $gproom .= ',childrenAges:' . implode(',', $room['childrenAges']);
            }
            return $gproom;
        }, $this->rooms);

        /*
        * это специальный костыль для GPTS,
        * т.к. они вместо канонической передачи массива через GET
        * принимают несколько одинаковых параметров, rooms[] не прокатывает
        */
        $props['rooms'] = implode('&rooms=', $gprooms);


        if (!empty($this->hotelCode)) {
            $props['hotelCode'] = $this->hotelCode;
        }

        if (!is_null($this->category)) {
            $props['category'] = $this->category;
        }

        if (is_array($this->hotelChains)) {
            $props['hotelChains'] = implode('&hotelChains=', $this->hotelChains);
        }

        if (is_array($this->mealType)) {
            $props['mealType'] = implode('&mealType=', $this->mealType);
        }

        return $props;

    }

    public function __get($n)
    {
        switch ($n) {
            case 'startDate':
                return $this->startDate;
            case 'endDate':
                return $this->endDate;
            case 'cityId':
                return $this->cityId;
            default:
                return parent::__get($n);
        }
    }
}
