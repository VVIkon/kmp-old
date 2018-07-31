<?php
/** класс работы с разделом Issuing GPTS API */
class TicketsApi extends GPTSApi {
  /**
  * вызов метода GPTS issueTicket
  * @param mixed[] $params структура параметров запроса согласно GPTS API
  * @return mixed[] ответ
  */
  public function issue($params)
  {
    return $this->apiClient->makeRequest('issueFlightTicket', $params, [], 'getpost');
  }

  /**
   * Вызов метода GPTS voucher
   * @param $params
   * @return bool|mixed[]
   */
  public function getEtickets($params)
  {
    return $this->apiClient->makeRequest('voucher', $params, [], 'get');
  }

  /**
   * Получить маршрутную квитанцию по указанному url
   * @param $url string
   */
  public function getETicketByUrl($url)
  {
    return $this->apiClient->httpRequest($url);
  }
}