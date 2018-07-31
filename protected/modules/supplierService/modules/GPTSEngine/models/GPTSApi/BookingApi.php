<?php

/** класс работы с разделом Booking GPTS API */
class BookingApi extends GPTSApi
{
    /**
     * вызов метода GPTS book
     * @param mixed[] $params структура параметров запроса согласно GPTS API
     * @return mixed[] ответ
     */
    public function book($params)
    {
        return $this->apiClient->makeRequest('book', $params, [], 'post');
    }

    /**
     * вызов метода GPTS paymentOptions
     * @param mixed[] $params структура параметров запроса согласно GPTS API
     * @return mixed[] ответ
     */
    public function paymentOptions($params)
    {
        return $this->apiClient->makeRequest('paymentOptions', $params, []);
    }

    /**
     * вызов метода GPTS paymentOptions
     * @param mixed[] $params структура параметров запроса согласно GPTS API
     * @return mixed[] ответ
     */
    public function checkStatus($params)
    {
        return $this->apiClient->makeRequest('checkstatus', $params, []);
    }
}