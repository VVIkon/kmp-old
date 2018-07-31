<?php

/**
 * Class FlightOfferFinder
 * Класс для поиска предложения по авиаперелёту
 */
class FlightOfferFinder extends OfferFinder
{
    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        parent::__construct($module);
        $this->namespace = $this->module->getConfig('log_namespace');
    }

    /**
     * Сохранить задание для поиска в кэше
     * @param $params
     * @return bool
     */
    public function makeSearchRequestTask($params)
    {
        $this->generateSearchToken();

        $request = $this->createRequest($params['requestDetails']);

        $token = $this->getExistedRequestToken($request);

        if ($token) {
            $result = $this->requestToken = $token;
        } else {

            parent::makeSearchRequestTask(
                [
                    'token' => $this->requestToken,
                    'offerType' => OfferFindersFactory::getOfferTypeByClassName(get_class($this)),
                ]
            );

            $result = $request->toCache($this->requestToken);
        }

        if ($result) {
            return $this->requestToken;
        }

        return false;
    }

    /**
     * Создание объекта запроса
     * @param $params
     * @return FlightSearchRequest
     */
    private function createRequest($params)
    {
        //Формирование массива возрастов детей по их количеству
        $generateFakeAges = function ($childrenCount, $infantsCount)
        {
            $ages = [];
            if (!empty($childrenCount) && is_numeric($childrenCount)) {
                $ages = array_fill(1, $childrenCount, 8);
            }

            if (!empty($infantsCount) && is_numeric($infantsCount)) {
                $ages = array_merge($ages, array_fill(1, $infantsCount, 1));
            }

            return $ages;
        };

        $request = new FlightSearchRequest($this->module, SearchRequestsFactory::FLIGHT_REQUEST_TYPE);

        $request->setRoute($params['route']['triptype'],$params['route']['trips']);

        if (!empty($params['searchBySchedule'])) {
            $request->setSchedule($params['searchBySchedule']['dateFrom'],$params['searchBySchedule']['dateTo']);
        }

        $request->clientId = (!empty($params['clientId'])) ? (int)$params['clientId'] : null;

        $request->flightClass =  (!empty($params['flightClass']))
            ? FlightClass::getIdByName($params['flightClass'])
            : FlightClass::ANY;
        $request->charter = $params['charter'];
        $request->regular = $params['regular'];
        $request->flexibleDays = (!empty($params['flexibleDays']))
            ? $params['flexibleDays']
            : 0;
        $request->adult = $params['adult'];
        $request->children = $params['children'];
        $request->infants = $params['infants'];

        $request-> childrenAges = $generateFakeAges($params['children'], $params['infants']);

        $request->directFlight = $params['directFlight'];
        $request->flightNumber = $params['flightNumber'];
        $request->supplierCode = $params['supplierCode'];
        $request->airlineCode = isset($params['airlineCode']) ? $params['airlineCode'] : [];
        $request->uniteOffers = $params['uniteOffers'];

        return $request;
    }

    /**
     * Поиск существующего поискового запроса с одинаковыми параметрами
     * @param $request
     * @return bool
     */
    protected function getExistedRequestToken($request)
    {
        $existedRequest = $request->getSimilarRequestByParams(
            [
                'clientId',
                'route',
                'flightClass',
                'flexibleDays',
                'regular',
                'charter',
                'adult',
                'children',
                'infants',
                'childrenAges',
                'directFlight',
                'flightNumber',
                'supplierCode',
                'airlineCode',
                //'uniteOffers'
            ]
        );

        if (!$existedRequest) {
            return false;
        } else {
            return $existedRequest['token'];
        }
    }

}
