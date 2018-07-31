<?php
/** класс работы с разделом Companies GPTS API */
class CompaniesApi extends GPTSApi {
  /**
  * вызов метода GPTS companies для получеия списка компаний
  * @param mixed[] $params структура параметров запроса согласно GPTS API
  * @return mixed[] ответ
  */
  public function getCompanies($params,$stream=false,$lang=false) {
    return $this->apiClient->makeRequest('companies',$params,[],'get',$stream,$lang);
  }

}