<?php

/** класс работы с разделом Flights GPTS API */
class FlightsApi extends GPTSApi
{
    /**
     * вызов метода GPTS flightInfo
     * @param mixed[] $params структура параметров запроса согласно GPTS API
     * @return mixed[] ответ
     */
    public function flightInfo($params)
    {
        /** @todo реализовать проверку параметров */
        return $this->apiClient->makeRequest(
            'flightInfo',
            $params,
            [400 => SupplierErrors::API_REQUEST_FAIL]
        );
    }

    /**
     * вызов метода GPTS prepareFlightBook
     * @param mixed[] $params структура параметров запроса согласно GPTS API
     * @return mixed[] ответ
     */
    public function prepareFlightBook($params)
    {
        return $this->apiClient->makeRequest(
            'prepareFlightBook',
            $params,
            [404 => SupplierErrors::OFFER_UNAVAILABLE],
            'post'
        );
    }

    /**
    * Вызов метода GPTS flightFares
    * @param mixed[] $params структура параметров запроса согласно GPTS API
    * @return mixed[] ответ
    */
    public function flightFares($params, $lang = false) {
        return $this->apiClient->makeRequest(
            'flightFares',
            $params,
            [],
            'get', 
            false,
            $lang
        );
    }


}