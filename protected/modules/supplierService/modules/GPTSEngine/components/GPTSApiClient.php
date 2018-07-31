<?php

/**
 * Class GPTSApiClient
 * Класс для выполнения доступа к REST-сервисам GPTS
 */
class GPTSApiClient extends HttpHelper
{

    /** @var KModule Модуль компонента */
    protected $module;

    /** @var string Ключ для авторизации запросов к api */
    private $apikey;

    /** @var string URL сервиса провайдера */
    private $svcUrl;

    /** @var string Логин сервиса */
    private $svcLogin;

    /** @var string Пароль сервиса */
    private $svcPass;

    /** @var string Company code or alias */
    private $svcCompanyCodeOrAlias;

    /** @var string token доступа к сервису */
    protected $token;

    /** @var int Код ошибки */
    private $errorCode;

    /**
     * Конструктор класса
     * @param $module object модуль класса
     * @throws KmpException
     */
    public function __construct($config, $module)
    {
        $this->module = $module;
        $this->init($config);

        if (!$this->getAuthToken()) {
            throw new KmpException(
                get_class(), __FUNCTION__,
                SupplierErrors::GPTS_API_AUTH_FAILED,
                ['url' => $this->svcUrl, 'login' => $this->svcLogin]
            );
        }
    }

    /**
     * Инициализация параметров объекта
     * @param $config array
     *
     * @todo здесь было 'current_api', понять, откуда оно взялось
     */
    public function init($config)
    {
        if (EnvironmentHelper::getEnvironmentType() == EnvironmentHelper::PRODUCTION) {
            $config['current_api'] = $config['prod_api'];
        } else {
            $config['current_api'] = $config['test_api'];
        }

        $this->apikey = $config['current_api']['authInfo']['key'];
        $this->svcLogin = $config['current_api']['authInfo']['login'];
        $this->svcPass = $config['current_api']['authInfo']['password'];
        $this->svcCompanyCodeOrAlias = isset($config['current_api']['authInfo']['companyCodeOrAlias'])
                                        ? $config['current_api']['authInfo']['companyCodeOrAlias']
                                        : null;

        $this->svcUrl = $config['current_api']['url'];
    }

    /**
     * Получение token'а авторизации
     * @return bool результат операции
     * @throws KmpException
     */
    public function getAuthToken($retry = 0)
    {
        $params = [
            'apiKey' => $this->apikey,
            'login' => $this->svcLogin,
            'password' => $this->svcPass,
        ];
        if (!empty($this->svcCompanyCodeOrAlias)) {
            $params['companyCodeOrAlias'] = $this->svcCompanyCodeOrAlias;
        }
        $httpHelper = new HttpHelper();
        $result = $httpHelper->httpRequest($this->svcUrl . 'authorization', $params, null, array('Content-Type:application/json'));

        if (!$result || $result['code'] != 200 || !isset($result['body'])) {
            if ($retry !== 0) {
                $retry--;
                sleep(5);
                return $this->getAuthToken($retry);
            } else {
                return false;
            }
        }

        $result = json_decode($result['body']);

        if (empty($result->token)) {
            if ($retry !== 0) {
                $retry--;
                sleep(2);
                return $this->getAuthToken($retry);
            } else {
                return false;
            }
        }

        $this->token = $result->token;

        return true;
    }

    /**
     * Выполнение запроса к сервису
     * @param string $action метод API
     * @param mixed[] $params параметры метода сервиса
     * @param mixed[] $errorCodes специально обрабатываемые коды ошибок
     * @param string $method метод запроса - get|post
     * @param bool $stream надо ли вернуть поток вместо объекта
     * @param string $lang код языка, на котором нужно вернуть ответ (ru,en,...)
     * @return mixed[]|bool результат
     * @throws KmpException
     */
    public function makeRequest(
        $action,
        $params,
        $errorCodes = [],
        $method = 'get',
        $stream = false,
        $lang = false,
        $retry = 0
    )
    {
        $httpHelper = new HttpHelper();

        if ($stream === true) {
            $requestfunc = 'httpRequestStream';
        } else {
            $requestfunc = 'httpRequest';
        }

        $headers = [];
        $headers[] = 'Accept-Charset: utf-8';

        if ($lang !== false) {
            switch ($lang) {
                case 'ru':
                    $headers[] = 'Accept-Language: ru';
                    break;
                case 'en':
                    $headers[] = 'Accept-Language: en';
                    break;
            }
        }
//
//        var_dump($this->svcUrl . $action);
//        var_dump($params);

        switch ($method) {
            case 'getpost':
                /** Burn in hell, GPTS! */
                $params = array_merge($params, [
                    'token' => $this->token
                ]);

                $headers[] = 'Content-Type:text/html';

                $result = $httpHelper->$requestfunc(
                    $this->svcUrl . $action,
                    $params,
                    [],
                    $headers
                );
                break;

            case 'post':
                $headers[] = 'Content-Type:application/json';

                $result = $httpHelper->$requestfunc(
                    $this->svcUrl . $action,
                    ['token' => $this->token],
                    $params,
                    $headers
                );
                break;

            case 'get':
                $params = array_merge($params, [
                    'token' => $this->token
                ]);

                //$headers[]='Content-Type:application/json';

                $result = $httpHelper->$requestfunc(
                    $this->svcUrl . $action,
                    $params,
                    null,
                    $headers
                );
                break;

            default:
                return false;
        }

//        var_dump($result);
//        exit;

        if ($result['code'] != 200) {
            if ($retry !== 0) {
                sleep(10);
                $retry--;
                return $this->makeRequest(
                    $action, $params, $errorCodes, $method, $stream, $lang, $retry
                );
            }

            if (array_key_exists($result['code'], $errorCodes)) {
                $msg = '';

                if (!empty($result['body'])) {
                    $req = json_decode($result['body'], true);
                    $msg = isset($req['msg']) ? $req['msg'] : '';
                }
                throw new KmpException(
                    get_class(), __FUNCTION__,
                    $errorCodes[(int)$result['code']],
                    ['url' => $this->svcUrl, 'request' => $action, 'msg' => $msg]
                );
            }
            throw new KmpException(
                get_class(), __FUNCTION__,
                SupplierErrors::API_REQUEST_ERROR,
                ['url' => $this->svcUrl, 'request' => $action, 'params' => array_merge($params, $result)]
            );
        } else {
            if ($stream === true) {
                return $result['stream'];
            } else {
                $body = json_decode($result['body'], true);
                return $body;
            }
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

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
}
