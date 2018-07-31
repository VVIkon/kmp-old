<?php

/** класс работы с разделом Accomodations GPTS API */
class AccomodationsApi extends GPTSApi
{
    /**
     * вызов метода GPTS hotelChangesLocations для получения списка городов с изменениями по отелям
     * @param mixed[] $params структура параметров запроса согласно GPTS API
     * @return mixed[] ответ
     */
    public function hotelChangesLocations($params, $stream = false, $lang = false, $retry = 0) {
        return $this->apiClient->makeRequest('hotelChangesLocations', $params, [], 'get', $stream, $lang, $retry);
    }

    /**
     * вызов метода GPTS hotels для получения списка отелей
     * @param mixed[] $params структура параметров запроса согласно GPTS API
     * @return mixed[] ответ
     */
    public function hotels($params, $stream = false, $lang = false, $retry = 0)
    {
        return $this->apiClient->makeRequest('hotels', $params, [], 'get', $stream, $lang, $retry);
    }

    /**
     * вызов метода GPTS hotelInfo для получения подробной информации по отелю
     * @param mixed[] $params структура параметров запроса согласно GPTS API
     * @return mixed[] ответ
     */
    public function hotelInfo($params, $stream = false, $lang = false, $retry = 0)
    {
        return $this->apiClient->makeRequest('hotelInfo', $params, [], 'get', $stream, $lang, $retry);
    }

    /**
     * With this method you can select an offer and specify tourists' details.
     * The prepareBook method is the first step of the booking process.
     * @param $params
     * @return bool|mixed[]
     */
    public function prepareAccommodationBook($params)
    {
        return $this->apiClient->makeRequest(
            'prepareAccommodationBook',
            $params,
            [404 => SupplierErrors::OFFER_UNAVAILABLE],
            'post'
        );
    }

    /**
     * Prepare modify for accommodation
     * @param $params
     * @return bool|mixed[]
     */
    public function prepareAccommodationModify($params)
    {
        return $this->apiClient->makeRequest(
            'prepareAccommodationModify',
            $params,
            [404 => SupplierErrors::OFFER_UNAVAILABLE],
            'post'
        );
    }

    /**
     * modifyAccommodationService
     * @param $params
     * @return bool|mixed[]
     */
    public function modifyAccommodationService($params)
    {
        return $this->apiClient->makeRequest(
            'modifyAccommodationService',
            $params,
            [404 => SupplierErrors::OFFER_UNAVAILABLE],
            'getpost'
        );
    }
}
