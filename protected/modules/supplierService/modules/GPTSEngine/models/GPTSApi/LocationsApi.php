<?php
/** класс работы с разделом Locations GPTS API */
class LocationsApi extends GPTSApi {
  /**
  * вызов метода GPTS locations для получеия списка стран,городов,...
  * @param mixed[] $params структура параметров запроса согласно GPTS API
  * @return mixed[] ответ
  */
  public function locations($params,$stream=false,$lang=false) {
    return $this->apiClient->makeRequest('locations',$params,[],'get',$stream,$lang);
  }

}
