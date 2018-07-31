<?php
/** класс работы с разделом Persons GPTS API */
class PersonsApi extends GPTSApi {
  /**
  * вызов метода GPTS companies для получеия списка компаний
  * @param mixed[] $params структура параметров запроса согласно GPTS API
  * @return mixed[] ответ
  */
  public function getPersons($params,$stream=false) {
    return $this->apiClient->makeRequest('persons',$params,[],'get',$stream);
  }

}
