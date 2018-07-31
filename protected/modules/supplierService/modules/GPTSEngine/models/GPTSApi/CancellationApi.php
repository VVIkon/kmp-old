<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/29/16
 * Time: 12:41 PM
 */
class CancellationApi extends GPTSApi
{
    /**
     * вызов метода GPTS conditions
     * @param mixed[] $params структура параметров запроса согласно GPTS API
     * @return mixed[] ответ
     */
    public function conditions($params)
    {
        return $this->apiClient->makeRequest('conditions', $params, [], 'get');
    }

    /**
     * вызов метода GPTS cancelService
     * @param mixed[] $params структура параметров запроса согласно GPTS API
     * @return mixed[] ответ
     */
    public function cancelService($params)
    {
        return $this->apiClient->makeRequest('cancelService', $params, [], 'getpost');
    }
}