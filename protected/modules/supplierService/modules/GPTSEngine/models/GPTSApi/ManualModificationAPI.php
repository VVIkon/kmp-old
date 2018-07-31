<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 17.04.17
 * Time: 13:14
 * класс работы с разделом manualModification GPTS API
 */
class ManualModificationAPI extends GPTSApi
{
    /**
     * вызов метода GPTS manualModification [post]
     * @param mixed[] $params структура параметров запроса согласно GPTS API
     * @return mixed[] ответ
     */
    public function setManualMofification($params)
    {
        return $this->apiClient->makeRequest('manualModification', $params, [], 'post');
    }

}