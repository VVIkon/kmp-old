<?php

/**
 * Клиент для KT API
 */
class UIApiClient
{
    /** @var array данные аутентификации */
    protected $authData;
    /** @var array пути к сервисам */
    protected $servicePaths;

    /**
     * Выберем настройки модуля и создадим $ApiToken
     * @param array $authData данные аутентификации
     * @param array $servicePaths данные путей к сервисам
     */
    public function __construct($authData, $servicePaths)
    {
        $this->authData = $authData;
        $this->servicePaths = $servicePaths;
        if (!Yii::app()->session->contains('serviceTokens')) {
            Yii::app()->session->add('serviceTokens', []);
        }
    }

    /**
     * Сервисная аутентификация
     * @param $serviceName название сервиса
     */
    private function authenticate($serviceName)
    {
        if (!isset($this->servicePaths[$serviceName])) {
            throw new Exception('Целевой сервис не существует');
        }
        if (!isset($this->authData['login']) || !isset($this->authData['pass'])) {
            throw new Exception('Не указаны данные для аутентификации');
        }

        $serviceUrl = $this->servicePaths[$serviceName];

        $response = (new HttpHelper())->httpPostRequest($serviceUrl . 'authenticate', [
            'login' => $this->authData['login'],
            'pass' => $this->authData['pass']
        ]);

        if ($response['code'] != 200) {
            throw new Exception('Ошибка HTTP: ' . $response['code']);
        }

        $authResult = json_decode($response['body'], true);

        if ($authResult['status'] == 0) {
            $serviceTokens = Yii::app()->session->get('serviceTokens');
            $serviceTokens[$serviceName] = $authResult['body']['token'];
            Yii::app()->session->add('serviceTokens', $serviceTokens);
            return true;
        } else {
            throw new Exception($authResult['errors']);
        }
    }

    /**
     * Выполнение запроса к сервису
     * @param string $serviceName название сервиса
     * @param string $action действие
     * @param array $params параметры метода сервиса
     * @param bool $isFinal если истина, не вызывать рекурсивно
     * @return mixed результат
     * @throws Exception
     */
    public function makeRestRequest($serviceName, $action, array $params, $isFinal = false)
    {
        if (!isset($this->servicePaths[$serviceName])) {
            throw new Exception('Неизвестный сервис');
        }

        $serviceUrl = $this->servicePaths[$serviceName];
        $serviceTokens = Yii::app()->session->get('serviceTokens');
        if (!isset($serviceTokens[$serviceName])) {
            $this->authenticate($serviceName);
            $serviceTokens = Yii::app()->session->get('serviceTokens');
        }
        $params['token'] = $serviceTokens[$serviceName];

        session_write_close();

        $serviceUrl = $this->servicePaths[$serviceName];
        $response = (new HttpHelper())->httpPostRequest($serviceUrl . $action, $params);

        if ($response['code'] != 200) {
            throw new Exception('Ошибка HTTP: ' . $response['code']);
        }

        $responseData = json_decode($response['body'], true);
        if ($responseData['status'] != 0 && ($responseData['errorCode'] == 2 || $responseData['errorCode'] == 3)) {
            if ($isFinal) {
                return $response['body'];
            } else {
                $this->authenticate($serviceName);
                return $this->makeRestRequest($serviceName, $action, $params, true);
            }
        }

        return $response['body'];
    }
}
