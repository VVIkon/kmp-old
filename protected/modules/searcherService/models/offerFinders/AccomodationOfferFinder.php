<?php

/**
 * Class AccomodationOfferFinder
 * Класс для поиска предложения по размещению
 */
class AccomodationOfferFinder extends OfferFinder
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
     * @return AccomodationSearchRequest
     */
    private function createRequest($params)
    {
        $request = new AccomodationSearchRequest($this->module, SearchRequestsFactory::ACCOMMODATION_REQUEST_TYPE);

        $request->supplierCode = hashtableval($params['supplierCode'], '');
        $request->hotelId = hashtableval($params['hotelIdKt'], '');
        $request->clientId = (!empty($params['clientId'])) ? (int)$params['clientId'] : null;
        $request->cityId = hashtableval($params['route']['city'], '');
        $request->dateFrom = hashtableval($params['route']['dateStart'], '');
        $request->dateTo = hashtableval($params['route']['dateFinish'], '');
        $request->flexibleDays = hashtableval($params['flexibleDates'], '');
        $request->freeOnly = hashtableval($params['freeOnly'], '');

        foreach ($params['rooms'] as $room) {
            $request->rooms[] = [
                'adults' => $room['adults'],
                'children' => count($room['childrenAges']),
                'childrenAges' => $room['childrenAges'] // childrenAges:7,8
            ];
        }

        $request->hotelCode = $params['hotelCode'];
        $request->hotelSupplier = $params['hotelSupplier'];
        $request->category = $params['category'];
        $request->hotelChains = $params['hotelChains'];

        if (isset($params['mealType']) && is_array($params['mealType'])) {
            $mealTypes = Yii::app()->db->createCommand()
                ->select('mealCode')
                ->from('ho_ref_meal')
                ->where(['in', 'mealCode', $params['mealType']])
                ->queryColumn();

            if (count($mealTypes) != 0) {
                $request->mealType = $mealTypes;
            }
        }

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
                'cityId',
                'dateTo',
                'dateFrom',
                'category',
                'hotelChains',
                'hotelName',
                'hotelCode',
                'hotelSupplier',
                'mealType',
                'flexibleDays',
                'freeOnly',
                'hotelId',
                'rooms'
            ]
        );

        if (!$existedRequest) {
            return false;
        } else {
            return $existedRequest['token'];
        }
    }

}
