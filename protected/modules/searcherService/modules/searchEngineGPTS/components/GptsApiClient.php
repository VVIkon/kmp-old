<?php

/**
 * Class GptsApiClient
 * Класс для выполнениядоступа к REST-сервисам GPTS
 */
class GptsApiClient extends HttpHelper
{

    /**
     * Модуль компонента
     * @var void
     */
    protected $module;

    /**
     * Ключ для авторизации запросов к api
     * @var string
     */
    private $apikey;

    /**
     * URL сервиса провайдера
     * @var string
     */
    private $svcUrl;

    /**
     * Логин сервиса
     * @var string
     */
    private $svcLogin;

    /**
     * Пароль сервиса
     * @var string
     */
    private $svcPass;

    /**
     * token доступа к сервису
     * @var string
     */
    protected $token;

    /**
     * Массив комманд провайдера
     * @var array
     */
    protected $actions;

    /**
     * Код ошибки
     * @var int
     */
    private $errorCode;

    /**
     * Конструктор класса
     * @param $module object модуль класса
     */
    public function __construct($config, $module)
    {
        $this->module = $module;
        $this->init($config);

        if (!$this->getAuthToken()) {
            return false;
        }

    }

    /**
     * Инициализация параметров объекта
     * @param $config array
     */
    public function init($config)
    {
        $this->apikey = $config['current_api']['authInfo']['key'];
        $this->svcLogin = $config['current_api']['authInfo']['login'];
        $this->svcPass = $config['current_api']['authInfo']['password'];

        $this->svcUrl = $config['current_api']['url'];
        $this->actions = $config['current_api']['actions'];
    }

    /**
     * Получение token'а авторизации
     * @return bool результат
     * @throws Exception
     */
    protected function getAuthToken()
    {
        $params = [
            'apiKey' => $this->apikey,
            'login' => $this->svcLogin,
            'password' => $this->svcPass
        ];

        $httpHelper = new HttpHelper();
        $result = $httpHelper->httpRequest($this->svcUrl . $this->actions['authorize'], $params,
            null, ['Content-Type:application/json']);

        if (!$result || !isset($result['body'])) {
            return false;
        }

        $result = json_decode($result['body']);

        if (empty($result->token)) {
            return false;
        }

        $this->token = $result->token;

        return true;
    }

    /**
     * Выполнение запроса к сервису
     * @param $params array параметры метода сервиса
     * @param bool $stream надо ли вернуть stream вместо массива
     * @return bool результат
     * @throws Exception
     */
    public function makeRequest($params, $stream = false)
    {
        $action = $this->actions[$params['requestType']];
        if (!$action) {
            return false;
        }

        $params = array_merge($params, [
            'token' => $this->token
        ]);

        $httpHelper = new HttpHelper();

        /** @todo здесь нужна будет обработка lang  */
        if (!$stream) {
            $result = $httpHelper->httpRequest(
                $this->svcUrl . $action,
                $params,
                null,
                ['Content-Type:application/json', 'Accept-Language: ru'],
                '',
                ''
            );
        } else {
            $result = $httpHelper->httpRequestStream(
                $this->svcUrl . $action,
                $params,
                null,
                ['Content-Type:application/json', 'Accept-Language: ru'],
                '',
                ''
            );
        }

        if ($result['code'] != 200) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Ответ ГПТС', 'Ошибка',
                [
                    'request' => $params,
                    'response' => $result
                ],
                LogHelper::MESSAGE_TYPE_ERROR, 'system.searcherservice.errors'
            );

            throw new KmpInvalidArgumentException(
                get_class(),
                __FUNCTION__,
                SearcherErrors::ERROR_REQUESTING_OFFERS,
                ['url' => $this->svcUrl, 'params' => array_merge($params, $result)]
            );
        }

        if (!$stream) {
            return json_decode($result['body'], true);
        } else {
            return $result['stream'];
        }
    }

    /**
     * Получение кода последней ошибки
     * @return int описание ошибки
     */
    public function getLastError()
    {
        return $this->errorCode;
    }
}
