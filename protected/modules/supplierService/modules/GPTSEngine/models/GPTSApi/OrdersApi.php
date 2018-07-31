<?php

/** класс работы с разделом Orders GPTS API */
class OrdersApi extends GPTSApi
{
    /**
     * вызов метода GPTS orders [get]
     * @param mixed[] $params структура параметров запроса согласно GPTS API
     * @return mixed[] ответ
     */
    public function orders_get($params)
    {
        return $this->apiClient->makeRequest('orders', $params, []);
    }

}