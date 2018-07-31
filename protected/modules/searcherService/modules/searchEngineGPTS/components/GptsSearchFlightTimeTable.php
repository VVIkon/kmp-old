<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 09.06.17
 * Time: 16:12
 */
class GptsSearchFlightTimeTable
{
    /** @var string namespace для записи логов */
    private $namespace;
    /** @var SearchEngineGPTSModule Модуль компонента */
    private $module;
    /** @var int Код ошибки */
    private $errorCode;
    /** @var ApiClient Клиент api внутрисервисной коммуникации */
    private $apiClient;
    /** @var GptsApiClient Клиент к api провайдера */
    private $gptsApiClient;
    /** @var array коды поставщиков из конфига */
    private $suppliers;

    /**
     * @param $module object
     */
    public function __construct()
    {
        $searcherService = Yii::app()->getModule('searcherService');
        $this->module = $searcherService->getModule('searchEngineGPTS');
        $this->namespace = $this->module->getConfig('log_namespace');
        $this->apiClient = new ApiClient($searcherService);

        if (!$this->init()) {
            return false;
        }
    }

    /**
     * Инициализация поискового модуля
     * @return bool
     */
    protected function init()
    {
        $config = $this->module->getConfig();
        if (!$this->checkConfig($config)) {
            return false;
        }

        if (EnvironmentHelper::getEnvironmentType() == EnvironmentHelper::PRODUCTION) {
            $config['current_api'] = $config['provider']['prod_api'];
        } else {
            $config['current_api'] = $config['provider']['test_api'];
        }

        $this->gptsApiClient = new GptsApiClient($config, $this->module);

        $this->suppliers = $config['suppliers'];
        return true;
    }
    /**
     * Проверка параметров конфигурации поискового модуля
     * @param $config
     * @return bool
     */
    protected function checkConfig($config)
    {
        if (empty($config)) {
            $this->errorCode = SearcherErrors::GPTS_CONFIG_SECTION_NOT_SET;
            return false;
        }

        $validator = new GptsConfigValidator($this->module);

        try {
            $validator->checkConfigParams($config);
        } catch (KmpInvalidArgumentException $ke) {

            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = $ke->getCode();

            return false;
        }
        return true;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /** Валидатор входных параметров
     * @param $params
     * @return bool
     */
    private function validateParams($params)
    {
        if (empty($params)) {
            $this->errorCode = SearcherErrors::INCORRECT_INPUT_PARAMS;
            return false;
        }
        $validator = new GptsConfigValidator($this->module);

        try {
            $validator->checkInputScheduleParams($params);
        } catch (KmpInvalidArgumentException $ke) {

            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = $ke->getCode();

            return false;
        }

        return true;
    }

    /** Формирование параметров запроса из входных параметров для одного направления
     * @param $params
     * @return mixed
     */
    private function prepareParams($params, $route)
    {
        $requestParams['supplierId']=null; // $params['supplierCode']
        $airlineCodes = StdLib::nvl($params['airlineCode'],[]);
        $requestParams['airlineCodes'] = null;
        // "airlineCode" : ["SU","S7"] --->  SU&airlineCodes=S7
        foreach ($airlineCodes as $airlineCode) {
            if(!is_null($requestParams['airlineCodes'])){
                $requestParams['airlineCodes'] = $requestParams['airlineCodes'] .'&airlineCodes=';
            }
            $requestParams['airlineCodes'] = $requestParams['airlineCodes']. $airlineCode;
        }
        $requestParams['routes'] = $route['from'] . '-' . $route['to'] . ',' . $route['date'].',P30D';
        $requestParams['offerLimit']=0;
        $requestParams['requestType']='searchSchedule';
        return $requestParams;
    }



    /** Получение расписания из кеша
     * @param $requestParams
     */
    private function getScheduleFromCache($requestParams)
    {

        if (isset($requestParams['supplierId']) ){
            $params['supplierCode'] = $requestParams['supplierId'];
        }
        if (isset($requestParams['airlineCodes'])) {
            $params['airlineCode'] = $requestParams['airlineCodes'];
        }
        $params['route'] = $requestParams['routes'];

        return ScheduleCacheRepository::getScheduleDataByParam($params);
    }

    /** Сохранение сырого (json) результата из GPTS
     * @param $rawShedule
     */
    private function setScheduleInCache($requestParams, $actualData, $rawSchedule)
    {
        if (empty($rawSchedule['flights'])){
            return false;
        }
        return ScheduleCacheRepository::setCacheScheduleByParam($requestParams, $actualData, $rawSchedule);
    }

    private function modifySchedule($itineraries)
    {
        foreach($itineraries as $itinerary){
            $duration = StdLib::nvl($itinerary['itinerary'][0]['duration']);
            $outItinerary['duration'] = $duration;      // длительность перелета (минут)

            $segments = StdLib::nvl($itinerary['itinerary'][0]['segments'],[]);
            foreach($segments as $segment) {
                 $outSegment['marketingAirline'] = StdLib::nvl($segment['marketingAirline']); // маркетинговая АК
                 $outSegment['operatingAirline'] = StdLib::nvl($segment['operatingAirline']); // перевозчик
                 $outSegment['flightNumber'] = StdLib::nvl($segment['flightNumber']);    // номер рейса. Выводится как S7-897
                 $outSegment['aircraftCode'] = StdLib::nvl($segment['aircraftCode']);    // код самолета
                 $outSegment['aircraftName'] = StdLib::nvl($segment['aircraftName']);    // название самолета
                 $depCode = StdLib::nvl($segment['departureAirportCode']);
                 $outSegment['departureAirportCode'] = $depCode;
                 $outSegment['departureAirportName'] = AirportsHelper::getAirPortNameRUByIATA($depCode);
                 $outSegment['departureDate'] = StdLib::nvl($segment['departureDate']);
                 $outSegment['departureTerminal'] = StdLib::nvl($segment['departureTerminal']);     // терминал отправления
                 $arvCode = StdLib::nvl($segment['arrivalAirportCode']);
                 $outSegment['arrivalAirportCode'] = $arvCode;
                 $outSegment['arrivalAirportName'] = AirportsHelper::getAirPortNameRUByIATA($arvCode);
                 $outSegment['arrivalDate'] = StdLib::nvl($segment['arrivalDate']);
                 $outSegment['arrivalTerminal'] = StdLib::nvl($segment['arrivalTerminal']);                          // терминал прибытия
                 $outSegment['stopQuantity'] = StdLib::nvl($segment['stopQuantity']);                               // остановки (зарезервировано на будущее)
                 $outSegment['duration'] = $duration;                                 // длительность перелета на сегменте (минут)
                 $outSegment['operationDays'] = StdLib::nvl($segment['operationDays']);                     // расписание рейса по дням недели*
                 $outSegment['timeTableValidStartDate'] = StdLib::nvl($segment['timeTableValidStartDate']);   // дата, с которой действует расписание
                 $outSegment['timeTableValidEndDate'] = StdLib::nvl($segment['timeTableValidEndDate']);     // дата, по которую действует расписание

                 $outItinerary['segments'][0] = $outSegment;
             }

            $offer['supplierCode'] = $itinerary['supplierCode'];       // поставщик
            $offer['itinerary'][0] = $outItinerary;

            $offers[] = $offer;
        }

        return StdLib::nvl($offers,[]);
    }
    /**
     *
     * @param $params
     * https://kmp.travel/gptour-test/api/searchFlightTimeTable
     * ?airlineCodes=SU&airlineCodes=S7
     * &routes=MOW-LED,2017-11-01,P7D&routes=LED-MOW,2017-11-07,P7D
     * &offerLimit=0
     * &requestType=searchSchedule
     * &token=c40ba9c3-dbbf-46d6-9862-e2a55f8cc31d
     * @return bool|mixed[]
     */
    public function scheduleSearcher($params)
    {
        $resultSearch = [];
        try {
            if (!$this->validateParams($params)){
                return false;
            }
            // Перебор по рейсам (туда, обратно,...)
            foreach ($params['route'] as  $route) {
                $requestParams = $this->prepareParams($params, $route);
                $rawSchedule = $this->getScheduleFromCache($requestParams);
                if (empty($rawSchedule) || count($rawSchedule) == 0) {
                    $rawSchedule = $this->gptsApiClient->makeRequest($requestParams, []);
                    $this->setScheduleInCache($requestParams, $route['date'], $rawSchedule);
                }
                if (isset($rawSchedule)) {
                    $outSerach['tripName'] = $route['from'] . '-' . $route['to'];
                    $outSerach['offers'] = $this->modifySchedule($rawSchedule['flights']);
                    $resultSearch['trips'][] = $outSerach;
                }
            }

        } catch (KmpInvalidArgumentException $ke) {
            throw new KmpInvalidArgumentException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::INCORRECT_INPUT_PARAMS,
                $params
            );
        }
        return $resultSearch;
    }
}