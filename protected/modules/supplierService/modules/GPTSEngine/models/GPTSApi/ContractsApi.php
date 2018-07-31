<?php

/** класс работы с разделом Accomodations GPTS API */
class ContractsApi extends GPTSApi
{
    /**
     * вызов метода GPTS contracts для получения списка собственных продуктов
     * @param mixed[] $params структура параметров запроса согласно GPTS API
     * @return mixed[] ответ
     */
    public function contracts($params, $stream = false, $lang = false, $retry = 0)
    {
        return $this->apiClient->makeRequest('contracts', $params, [], 'get', $stream, $lang, $retry);
    }
}
