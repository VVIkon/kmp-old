<?php

/**
 * Class UtkClient
 * Класс для реализации запросов к УТК
 */
class UtkClient extends RestClientController
{
    /**
     * Ссылка на объект модуля
     * @var object
     */
    private $_module;

    /**
     * Код ошибки
     * @var int
     */
    private $_errorCode;

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct($module)
    {
        $this->_module = $module;

        $this->_errorCode = $this->_getServiceOptions();
        $this->_errorCode = $this->_getAuthData();
        $this->_errorCode = $this->getAuthToken();
    }

    /**
     * Получение настроек сервиса
     * @return bool результат
     */
    private function _getServiceOptions()
    {

        $svcInfo = $this->_module->getConfig('UtkService');//baseURL

        if (empty($svcInfo) || !isset($svcInfo['baseURL'])) {
            return false;
        }

        $this->_svcUrl = $svcInfo['baseURL'];
        return true;
    }

    /**
     * Получение учётных данных для авторизации
     * @return bool результат
     */
    protected function _getAuthData()
    {

        $authData = $this->_module->getConfig('authdata');

        if (empty($authData) || !isset($authData['login']) || !isset($authData['pass'])) {
            return false;
        }

        $this->svcName = $authData['login'];
        $this->svcPass = $authData['pass'];

        /*$domainAccount = YII::app()->params['domainAccessAccount'];

        $this->webSrvLogin = (!empty($domainAccount['login'])) ? $domainAccount['login'] : null;
        $this->webSrvPass = (!empty($domainAccount['password'])) ? $domainAccount['password'] : null;*/

        if (!empty($this->svcName) && !empty($this->svcPass)) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Получение token'а авторизации
     * @return bool результат
     * @throws Exception
     */
    protected function getAuthToken()
    {
        $params = [
            'login' => $this->svcName,
            'pass' => $this->svcPass,
        ];

        $httpHelper = new HttpHelper();

        $result = $httpHelper->httpRequest($this->_svcUrl . 'authenticate', null,
            $params, array('Content-Type:application/json'), $this->webSrvLogin, $this->webSrvPass);

        LogHelper::logExt(
            __CLASS__, __FUNCTION__,
            "Запрос {$this->_svcUrl} authenticate", 'Результат запроса аутентификации УТК',
            $result,
            LogHelper::MESSAGE_TYPE_INFO,
            'system.orderservice.utkrequests'
        );

        if (!$result || !isset($result['body'])) {
            return false;
        }

//todo убрать после того как будет убран из ответа от УТК некорректный символ перед началом JSON
        $result = preg_replace('/^\S*\{/', '{', $result);
        $result = json_decode($result['body']);

        LogHelper::logExt(
            __CLASS__, __FUNCTION__,
            "Запрос {$this->_svcUrl} authenticate", 'Результат запроса аутентификации УТК после удаления символа Васи',
            $result,
            LogHelper::MESSAGE_TYPE_INFO,
            'system.orderservice.utkrequests'
        );

        if ($result === null || $result->status != 0) {
            return false;
        }

        $this->token = $result->result->token;
        return true;
    }

    /**
     * @deprecated
     */
    /*
    public function makeRestRequest($action, $params) {

        $result = parent::makeRestRequest($action,$params);

        //todo убрать после того как будет убран из ответа от УТК некорректный символ перед началом JSON
        $result = preg_replace('/^\S*\{/','{',$result);


        $result = CJSON::decode($result);

        return $result;
    } */

    /**
     * Метод REST-запросов к УТК
     * @param string $action действие (последняя часть урла)
     * @param array $params параметры запроса
     * @param bool $stream если истина, вернуть поток вместо обработанных данных
     */
    public function makeRestRequest($action, $params, $stream = false)
    {
        if (!$action) {
            return false;
        }

        $params = array_merge($params, ['token' => $this->token]);

        stream_filter_register('devasya_filter', 'DeVasyaStreamFilter');

        $httpHelper = new HttpHelper();

//        var_dump($this->_svcUrl . $action);
//        var_dump(json_encode($params));
        LogHelper::logExt(
            __CLASS__, __FUNCTION__,
            'Запрос ' . $this->_svcUrl . $action, '',
            json_encode($params),
            LogHelper::MESSAGE_TYPE_INFO,
            'system.orderservice.utkrequests'
        );

        $result = $httpHelper->httpRequestStream($this->_svcUrl . $action, null,
            $params, array('Content-Type: application/json'), $this->webSrvLogin, $this->webSrvPass);

//        var_dump($result);
//        exit;

        LogHelper::logExt(
            __CLASS__, __FUNCTION__,
            'Запрос ' . $this->_svcUrl . $action, 'HTTP-код ответа: ' . $result['code'],
            $params,
            LogHelper::MESSAGE_TYPE_INFO,
            'system.orderservice.utkrequests'
        );

        if ($result['code'] != 200) {
            return false;
        }

        stream_filter_append($result['stream'], 'devasya_filter', STREAM_FILTER_READ);

//        LogHelper::logExt(
//            __CLASS__, __FUNCTION__,
//            'Запрос ' . $this->_svcUrl . $action, 'Ответ: ' . $result['code'],
//            CJSON::decode(stream_get_contents($result['stream'])),
//            LogHelper::MESSAGE_TYPE_INFO,
//            'system.orderservice.utkrequests'
//        );

//        var_dump(CJSON::decode(stream_get_contents($result['stream'])));
//        exit;

        if ($stream) {
            return $result['stream'];
        } else {
            return CJSON::decode(stream_get_contents($result['stream']));
        }
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->_errorCode;
    }

}
