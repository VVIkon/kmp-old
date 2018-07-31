<?php

/**
 * Class ServiceWorkflowManager
 * Класс для управления услугой
 */
class ServiceWorkflowManager extends Validator
{

    const ACTION_SERVICE_CREATE = 1;
    const ACTION_BOOK_START = 2;
    const ACTION_BOOK_COMPLETE = 3;
    const ACTION_CANCEL = 4;
    const ACTION_PAY_START = 5;
    const ACTION_PAY_FINISH = 6;
    const ACTION_ISSUE_TICKETS = 7;
    const ACTION_DONE = 8;

    const CALLED_FROM_OWM = 0;
    const CALLED_FROM_FE = 1;

    /**
     * Код ошибки
     * @var int
     */
    private $errorCode;

    /**
     * Используется для хранения ссылки
     * на модуль работы с утк
     * @var
     */
    private $_utkModule;

    /**
     * Используется для хранения ссылки
     * на модуль работы с заявками
     * @var
     */
    private $_orderModule;

    /**
     * namespace для записи логов
     * @var string
     */
    private $namespace;

    /**
     * Массив доступных действий
     * @var array
     */
    private static $actions = [
        self::ACTION_SERVICE_CREATE => 'ServiceCreate',
        self::ACTION_BOOK_START => 'BookStart',
        self::ACTION_BOOK_COMPLETE => 'BookComplete',
        self::ACTION_CANCEL => 'Cancel',
        self::ACTION_PAY_START => 'PayStart',
        self::ACTION_PAY_FINISH => 'PayFinish',
        self::ACTION_ISSUE_TICKETS => 'IssueTickets',
        self::ACTION_DONE => 'Done'
    ];

    /**
     * Конструктор класса
     * @param $module object
     */
    public function __construct()
    {

        $this->namespace = "system.orderservice";
        $this->_utkModule = YII::app()->getModule('orderService')->getModule('utk');
        $this->_orderModule = YII::app()->getModule('orderService')->getModule('order');
        $this->module = YII::app()->getModule('orderService');
    }

    /**
     * Запуск команды согласно параметрам
     * @param $params array параметры команды
     * @return bool
     */
    public function runAction($params)
    {
        if (!$this->checkRunAction($params)) {
            return false;
        }

        $action = self::getActionIdByName($params['action']);

        $notSecuredActions = $this->module->getConfig('noUserTokenActionsCheck');

        if (empty($params['usertoken']) &&
            (empty($notSecuredActions) || !in_array($params['action'], $notSecuredActions))
        ) {
            $this->errorCode = OrdersErrors::CANNOT_GET_CURRENT_USER_RIGHTS;
            return false;
        } else {
            $userToken = isset($params['usertoken']) ? $params['usertoken'] : '';
        }

        try {
            switch ($action) {
                case self::ACTION_SERVICE_CREATE;
                    /** @todo выпилено, никому ничего не дает сделать */
                    /*
                    if (!$this->checkActionRights(self::ACTION_SERVICE_CREATE, $userToken)) {
                        return false;
                    }*/

                    if (!$this->checkServiceCreate($params['actionParams'])) {
                        return false;
                    }

                    return $this->ServiceCreate($params);

                    break;
                case self::ACTION_BOOK_START;

                    /** @todo требуется реализация */
                    /*
                    if (!$this->checkActionRights(self::ACTION_BOOK_START, $userToken)) {
                        return false;
                    }*/

                    if (!$this->checkBookStart($params['actionParams'])) {
                        return false;
                    }

                    return $this->bookStart($params);

                    break;
                case self::ACTION_BOOK_COMPLETE;
                    if (!$this->checkBookComplete($params['actionParams'])) {
                        return false;
                    }

                    return $this->bookComplete($params);
                case self::ACTION_PAY_START;
                    if (!$this->checkPayStart($params)) {
                        return false;
                    }

                    // посчитаем сумму Остаток к оплате по услуге в валюте поставщика с учетом выставленных счетов
                    $OrderService = OrdersServicesRepository::findById($params['serviceId']);
                    $OrderService->calculateRestPaymentAmount();
                    $OrderService->save();

                    return $this->payStartPostAction($params);
                case self::ACTION_PAY_FINISH;

                    /** @todo требуется реализация */
                    /*
                    if (!$this->checkActionRights(self::ACTION_PAY_FINISH, $userToken)) {
                        return false;
                    }*/

                    if (!$this->checkPayFinish($params)) {
                        return false;
                    }

                    return $this->payFinishPostAction($params);
                case self::ACTION_ISSUE_TICKETS;

                    /** @todo требуется реализация */
                    /*
                    if (!$this->checkActionRights(self::ACTION_PAY_FINISH, $userToken)) {
                        return false;
                    }*/

                    if (!$this->checkIssueTickets($params)) {
                        return false;
                    }

                    if (!$this->issueTickets($params)) {
                        return false;
                    }

                    $result = $this->runDelegate(
                        WorkflowDelegatesFactory::GET_ETICKETS,
                        [
                            'serviceId' => $params['serviceId'],
                            'usertoken' => $params['usertoken']
                        ]
                    );

                    break;
                case self::ACTION_DONE;

                    /** @todo требуется реализация */
                    /*
                    if (!$this->checkActionRights(self::ACTION_DONE, $userToken)) {
                        return false;
                    }*/
                    /* todo раскомментировать после реализации обработки счетов */
                    /*if (!$this->checkDone($params)) {
                        return false;
                    }*/

                    $result = $this->runDelegate(
                        WorkflowDelegatesFactory::SET_SERVICE_STATUS_DELEGATE,
                        [
                            'serviceId' => $params['serviceId'],
                            'status' => ServicesForm::SERVICE_STATUS_DONE
                        ]
                    );

                    return $result;
                    break;
                case self::ACTION_IMPORT;
                    $this->checkImportService($params);
                    $result = $this->importService($params);
                    return $result;
                    break;
            }
        } catch (KmpInvalidSettingsException $kse) {

            LogHelper::logExt(
                $kse->class,
                $kse->method,
                $this->module->getCxtName($kse->class, $kse->method),
                $this->module->getError($kse->getCode()),
                $kse->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $kse->getCode();
            return false;
        }

        return true;
    }

    /**
     * Проверка наличия валидных услуг в заявке
     * @param $params array
     * @return bool
     */
    public function checkValidServicesExist($params)
    {

        $utkValidator = $this->_orderModule->UtkValidator($this->_orderModule);

        $services = empty($params['Services']) ? [] : $params['Services'];
        $tourists = empty($params['Tourists']) ? [] : $params['Tourists'];

        if (!$utkValidator->checkValidUtkServicesExists($services, $tourists)) {
            $this->errorCode = $utkValidator->getLastError();
            return false;
        };

        return true;
    }

    /**
     * Проверка возможности удаления связи между туристом и услугой
     * @param $params array
     */
    public function checkCanRemoveTouristFromService($params)
    {

        $touristValidator = $this->_orderModule->TouristsValidator($this->_orderModule);

        if (!$touristValidator->checkCanRemoveTouristFromOrderService($params)) {
            $this->errorCode = $touristValidator->getLastError();
            return false;
        };

        return true;
    }

    /**
     * Проверка прав доступа пользователя для указанного метода
     * @param $action
     * @return bool
     */
    private function checkActionRights($action, $userToken)
    {

        try {
            switch ($action) {
                case self::ACTION_SERVICE_CREATE :
                    $this->checkRights([
                        'OR' => [
                            RightsRegister::RIGHT_ORDERS_CREATE,
                            RightsRegister::RIGHT_OWN_COMPANY_ORDERS_CREATE,
                            RightsRegister::RIGHT_OTHER_COMPANIES_ORDERS_CREATE
                        ]
                    ], $userToken);
                    break;
                /*                case self::ACTION_ADD_SERVICE :
                break;*/
            }

        } catch (KmpInvalidSettingsException $kse) {

            LogHelper::logExt(
                $kse->class,
                $kse->method,
                $this->module->getCxtName($kse->class, $kse->method),
                $this->module->getError($kse->getCode()),
                $kse->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = OrdersErrors::CANNOT_GET_CURRENT_USER_RIGHTS;
            return false;

        } catch (KmpInvalidUserRightsException $kure) {

            LogHelper::logExt(
                $kure->class,
                $kure->method,
                $this->module->getCxtName($kure->class, $kure->method),
                $this->module->getError($kure->getCode()),
                $kure->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = OrdersErrors::NOT_ENOUGH_USER_RIGHTS;
            return false;
        }
        return true;
    }

    /**
     * Проверка прав пользователя на выполнения операций
     * @param $rightsToCheck проверяемые права
     */
    private function checkRights($rightsToCheck, $userToken)
    {

        $apiClient = new ApiClient($this->module);
        $profile = $apiClient->getUserProfileByToken($userToken);

        $rightsValidator = new UserRightsValidator($this->module, $profile);
        $rightsValidator->setCurrentUserProfile($profile);

        foreach ($rightsToCheck as $rightGroup) {

            $groupsRight = false;

            foreach ($rightGroup as $right) {
                switch ($right) {
                    case RightsRegister::RIGHT_ORDERS_CREATE :
                        $checkResult = $rightsValidator->checkRight($right, []);
                        break;
                    case RightsRegister::RIGHT_OWN_COMPANY_ORDERS_CREATE :
                        $checkResult = $rightsValidator->checkRight($right, []);
                        break;
                    case RightsRegister::RIGHT_OTHER_COMPANIES_ORDERS_CREATE :
                        $checkResult = $rightsValidator->checkRight($right, []);
                        break;
                }

                if ($checkResult == true) {
                    $groupsRight = true;
                    break;
                }
            }

            if ($groupsRight == false) {
                throw new KmpInvalidUserRightsException(
                    get_class($this),
                    __FUNCTION__,
                    OrdersErrors::NOT_ENOUGH_USER_RIGHTS,
                    [
                        'rightId' => print_r($rightGroup, 1),
                        'userId' => !empty(Yii::app()->user->getState('userProfile')['userId'])
                            ? Yii::app()->user->getState('userProfile')['userId']
                            : '',
                        'isGuest' => empty($profile) == 1 ? 'true' : 'false'
                    ]
                );
            }
        }

        return true;
    }

    /**
     * Валидация параметров запуска действия
     * @param $params
     */
    public function checkRunAction($params)
    {

        $actionValidator = new SwmActionsValidator($this->module);

        try {
            $actionValidator->checkRunActionParams($params);

        } catch (KmpInvalidSettingsException $kse) {

            LogHelper::logExt(
                $kse->class,
                $kse->method,
                $this->module->getCxtName($kse->class, $kse->method),
                $this->module->getError($kse->getCode()),
                $kse->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = $kse->getCode();
            return false;

        } catch (KmpInvalidArgumentException $kae) {

            LogHelper::logExt(
                $kae->class,
                $kae->method,
                $this->module->getCxtName($kae->class, $kae->method),
                $this->module->getError($kae->getCode()),
                $kae->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = $kae->getCode();
            return false;

        }
        return true;
    }

    private function checkServiceCreate($params)
    {
        $servicesValidator = $this->_orderModule->ServicesValidator($this->_orderModule);

        try {
            $servicesValidator->checkServiceCommonParams($params);
            $servicesValidator->checkServiceCreateParams($params['serviceParams']);
        } catch (KmpInvalidArgumentException $kae) {
            LogHelper::logExt(
                $kae->class,
                $kae->method,
                $this->module->getCxtName($kae->class, $kae->method),
                $this->module->getError($kae->getCode()),
                $kae->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $kae->getCode();
            return false;
        }
        return true;
    }

    /**
     * Проверка параметров бронирования услуги
     * @param $params array
     * @return bool
     */
    private function checkBookStart($params)
    {
        $servicesValidator = $this->_orderModule->ServicesValidator($this->_orderModule);

        try {
            $servicesValidator->checkBookStartCommonParams($params);
        } catch (KmpInvalidArgumentException $kae) {
            LogHelper::logExt(
                $kae->class,
                $kae->method,
                $this->module->getCxtName($kae->class, $kae->method),
                $this->module->getError($kae->getCode()),
                $kae->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $kae->getCode();
            return false;
        }
        return true;
    }

    private function checkBookComplete($params)
    {
        $servicesValidator = $this->_orderModule->ServicesValidator($this->_orderModule);

        try {
            $servicesValidator->checkBookCompleteParams($params);
        } catch (KmpInvalidArgumentException $kae) {
            LogHelper::logExt(
                $kae->class,
                $kae->method,
                $this->module->getCxtName($kae->class, $kae->method),
                $this->module->getError($kae->getCode()),
                $kae->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $kae->getCode();

            return false;
        }
        return true;
    }

    /**
     * Проверка параметров и условий выполнения команды PayStart
     * @param $params array
     * @return bool
     */
    private function checkPayStart($params)
    {
        $servicesValidator = $this->_orderModule->ServicesValidator($this->_orderModule);

        $params['actionParams']['serviceId'] = isset($params['serviceId'])
            ? $params['serviceId']
            : '';

        try {
            $servicesValidator->checkPayStartParams($params['actionParams']);
        } catch (KmpInvalidArgumentException $kae) {

            LogHelper::logExt(
                $kae->class,
                $kae->method,
                $this->module->getCxtName($kae->class, $kae->method),
                $this->module->getError($kae->getCode()),
                $kae->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $kae->getCode();

            return false;
        }
        return true;
    }

    /**
     * Проверка параметров и условий выполнения команды PayFinish
     * @param $params array
     * @return bool
     */
    private function checkPayFinish($params)
    {

        $servicesValidator = $this->_orderModule->ServicesValidator($this->_orderModule);

        $params['actionParams']['serviceId'] = isset($params['serviceId'])
            ? $params['serviceId']
            : '';

        try {
            $servicesValidator->checkPayFinishParams($params['actionParams']);

        } catch (KmpInvalidArgumentException $kae) {

            LogHelper::logExt(
                $kae->class,
                $kae->method,
                $this->module->getCxtName($kae->class, $kae->method),
                $this->module->getError($kae->getCode()),
                $kae->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $kae->getCode();

            return false;
        }
        return true;
    }

    private function checkIssueTickets($params)
    {
        $servicesValidator = $this->_orderModule->ServicesValidator($this->_orderModule);

        $params['actionParams']['serviceId'] = isset($params['serviceId'])
            ? $params['serviceId']
            : '';

        try {
            $servicesValidator->checkIssueTicketsParams($params['actionParams']);
        } catch (KmpInvalidArgumentException $kae) {
            LogHelper::logExt(
                $kae->class,
                $kae->method,
                $this->module->getCxtName($kae->class, $kae->method),
                $this->module->getError($kae->getCode()),
                $kae->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $kae->getCode();

            return false;
        }
        return true;
    }

    /**
     * Проверка параметров команды для завершения оформления услуги
     * @param $params
     * @return bool
     */
    private function checkDone($params)
    {
        $servicesValidator = $this->_orderModule->ServicesValidator($this->_orderModule);

        try {
            $servicesValidator->checkDoneParams($params['actionParams']);
        } catch (KmpInvalidArgumentException $kae) {
            LogHelper::logExt(
                $kae->class,
                $kae->method,
                $this->module->getCxtName($kae->class, $kae->method),
                $this->module->getError($kae->getCode()),
                $kae->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $kae->getCode();

            return false;
        }
        return true;
    }

    /**
     * Создание сервиса
     * @param mixed[] $params - параметры для создания сервиса
     * @return int|false - serviceId созданного сервиса или false в случае ошибки
     */
    private function ServiceCreate($params)
    {
        $getOfferParams = array_merge(
            $params['actionParams']['serviceParams'],
            [
                'serviceType' => $params['actionParams']['serviceType'],
                'token' => $params['token'],
                'usertoken' => $params['usertoken']
            ]
        );

        $apiClient = new ApiClient($this->module);
        $getOfferResponse = $apiClient->makeRestRequest('supplierService', 'GetOffer', $getOfferParams);
        $offerData = json_decode($getOfferResponse, true);

        if ($offerData === null) {
            throw new KmpException(
                get_class(), __FUNCTION__,
                OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR,
                ['action' => 'getOffer', 'errorMessage' => 'SupplierService response is not JSON', 'errorText' => json_encode($getOfferResponse)]
            );
        } elseif ($offerData['status'] !== 0) {
            if ($offerData['errorCode'] == 803) {
                throw new KmpException(
                    get_class(), __FUNCTION__,
                    OrdersErrors::OFFER_ID_NOT_EXISTENT,
                    ['serviceType' => $params['actionParams']['serviceType']]
                );
            } else {
                throw new KmpException(
                    get_class(), __FUNCTION__,
                    OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR,
                    ['action' => 'getOffer', 'supplierSrvErrorCode' => $offerData['errorCode']]
                );
            }
        }

        $service = ServicesFactory::createService($params['actionParams']['serviceType']);

        if (!$service) {
            $this->errorCode = OrdersErrors::CANNOT_CREATE_SERVICE;
            return false;
        }

//        $currencyForm = new CurrencyForm();
//
//        $netSum = $currencyForm->getAmountInCurrency(
//            $currencyForm->getCurrencyIdByCode($offerData['body']['price']['currency']),
//            $currencyForm->getCurrencyIdByCode($offerData['body']['price']['saleCurrency']),
//            $offerData['body']['price']['amountNet']
//        );

        $commission = empty($offerData['body']['price']['commisson']) ? 0 : $offerData['body']['price']['commisson'];

        $CurrencyRates = CurrencyRates::getInstance();

        $serviceInfo = [
            'serviceUtkId' => null,
            'serviceGptsId' => null,
            'status' => ServicesForm::SERVICE_STATUS_NEW,
            'serviceType' => $params['actionParams']['serviceType'],
            'serviceTour' => null,
            'offerId' => 0, /** @todo сюда получить id оффера */
            'startDateTime' => null, /** @todo вычислить из данных оффера */
            'endDateTime' => null, /** @todo вычислить из данных оффера */
            //  'serviceDateUpdate'=>'2016-01-19', /** @todo timestamp? */
            'supplierPrice' => $offerData['body']['price']['amountNet'],
            'saleSum' => $offerData['body']['price']['amountGross'],
            'commission' => $commission,
            'supplierCurrency' => $CurrencyRates->getIdByCode($offerData['body']['price']['currency']),
            'saleCurrency' => $CurrencyRates->getIdByCode($offerData['body']['price']['saleCurrency']),
            'offline' => 0,
            'orderId' => $params['orderId'],
            'extra' => '',
            'cityId' => null, /** @todo вычислить из данных оффера */
            'countryId' => null, /** @todo вычислить из данных оффера */
            'serviceName' => 'Перелет', /** @todo вычислить из данных оффера */
            'supplierId' => 0,
            'refNum' => null
        ];

        if (!$service->setAttributes($serviceInfo)) {
            $this->errorCode = OrdersErrors::CANNOT_CREATE_SERVICE;
            return false;
        }


        $offer = OffersFactory::createOffer($service->serviceType);

        if (!$offer) {
            LogHelper::log(PHP_EOL . get_class() . '.' . __FUNCTION__ . PHP_EOL .
                'Неизвестный тип услуги для ' . print_r($service, 1), 'trace',
                $this->_namespace . '.errors');
            $this->errorCode = OrdersErrors::UNKNOWN_SERVICE_TYPE;
            return false;
        }

        $offer->setAttributes(['offerData' => $offerData['body']]);

        if (!$offer->save()) {
            throw new KmpException(
                get_class(), __FUNCTION__,
                OrdersErrors::CANNOT_CREATE_OFFER,
                []
            );
        }

        $service->setOfferId($offer->getOfferId());

        $service->setServiceName($offer->getServiceName());
        $service->setStartDateTime($offer->getStartDateTime());
        $service->setEndDateTime($offer->getEndDateTime());
        $service->setCityId($offer->getCityId());
        $service->setCountryId($offer->getCountryId());
        $service->setSupplierId($offer->getSupplierId());

        $serviceId = $service->save();

        if (!$serviceId) {
            throw new KmpException(
                get_class(), __FUNCTION__,
                OrdersErrors::CANNOT_CREATE_SERVICE,
                ['offerId' => $offer->getOfferId()]
            );
        } else {
            return $serviceId;
        }

        /*
          $service = new Service();
          $apiClient = new ApiClient($this->module, 'searcherService');
          $apiClient->getOffer($params['actionParams']['serviceType'],$params['actionParams']['offerKey']);
        */
    }

    /**
     * Запуск процесса бронирования услуги
     * @param $params array
     * @return bool|void
     */
    public function bookStart($params)
    {
        $serviceInfo = ServicesForm::getServiceById($params['serviceId']);

        $service = ServicesFactory::createService($serviceInfo['ServiceType']);
        $service->load($params['serviceId']);
        $service->agreementSet = true;
        $service->save();

        $svcBookingParams = [
            'orderId' => $serviceInfo['OrderID'],
            'serviceId' => $serviceInfo['ServiceID'],
            'serviceType' => $serviceInfo['ServiceType'],
            'supplierId' => $serviceInfo['SupplierID'],
            'gateOrderId' => $params['gateOrderId']
        ];

        $offer = OffersFactory::createOffer($serviceInfo['ServiceType']);
        $offer->load($serviceInfo['OfferID']);

        $engineData = $this->getSupplierServicesInfo($serviceInfo['OrderID']);

        switch ($serviceInfo['ServiceType']) {
            case ServicesFactory::FLIGHT_SERVICE_TYPE :

                $svcBookingParams['supplierOfferData']['offerKey'] = $offer->offerData['offerKey'];
                $svcBookingParams['supplierOfferData']['engineDataList'] = $engineData;

                $touristsInfo = TouristForm::getServiceTourists($serviceInfo['ServiceID']);

                if (!isset($touristsInfo) || count($touristsInfo) == 0) {
                    $svcBookingParams['supplierOfferData']['tourists'] = [];
                }

                foreach ($touristsInfo as $touristInfo) {

                    $tourist = new TouristForm($this->namespace);
                    try {
                        $tourist->loadTouristByID($touristInfo['TouristID']);
                    } catch (KmpDbException $kae) {

                        LogHelper::logExt(
                            $kae->class,
                            $kae->method,
                            $this->module->getCxtName($kae->class, $kae->method),
                            $this->module->getError($kae->getCode()),
                            $kae->params,
                            LogHelper::MESSAGE_TYPE_ERROR,
                            $this->namespace . '.errors'
                        );
                        $this->errorCode = $kae->getCode();
                        return false;
                    }
                    $citizenship = CountriesMapperHelper::getCountryIdMatched(
                        CountriesMapperHelper::GPTS_SUPPLIER_ID,
                        $tourist->touristDoc['citizenship']
                    );

                    if (empty($tourist->touristDoc['citizenship']) || empty($citizenship)) {
                        throw new KmpInvalidArgumentException(
                            get_class($this),
                            __FUNCTION__,
                            OrdersErrors::INCORRECT_TOURIST_CITIZENSHIP,
                            ['touristInfo' => $tourist->touristDoc]
                        );
                    }

                    $svcBookingParams['supplierOfferData']['tourists'][] = [
                        'id' => $tourist['TouristID'],
                        'citizenshipId' => CountriesMapperHelper::getCountryIdMatched(
                            CountriesMapperHelper::GPTS_SUPPLIER_ID,
                            $tourist->touristDoc['citizenship']
                        ),
                        'email' => $tourist->touristBase['email'],
                        'phone' => $tourist->touristBase['phone'],
                        'passport' => [
                            'id' => $tourist->touristDoc['touristDocId'],
                            'number' => $tourist->touristDoc['docSerial'] . $tourist->touristDoc['docNumber'],
                            'issueDate' => $tourist->touristDoc['validFrom'],
                            'expiryDate' => $tourist->touristDoc['validTill']
                        ],
                        'sex' => $tourist->touristBase['sex'],
                        'lastName' => $tourist->touristDoc['surname'],
                        'firstName' => $tourist->touristDoc['name'],
                        'birthdate' => $tourist->touristBase['birthDate'],
                        'bonusCard' => [
                            'id' => $touristInfo['loyalityProgramId'],
                            'cardNumber' => $touristInfo['mileCard'],
                            'airLine' => '' //todo необходимо выяснить откуда брать этот параметр
                        ]
                    ];
                }
                break;
            default :
                return false;
        }

        $svcBookingParams['usertoken'] = $params['usertoken'];
        $apiClient = new ApiClient($this->module);

//        var_dump($svcBookingParams);
//        exit;
//        var_dump(json_encode($svcBookingParams));
//        exit;

        $response = json_decode($apiClient->makeRestRequest('supplierService', 'ServiceBooking', $svcBookingParams), true);
//
//        var_dump($response);
//        exit;

        if (RestException::isArrayRestException($response)) {

            $error = SupplierServiceHelper::translateSupplierSvcErrorId($response['errorCode']);

            $response = [
                'bookStartResult' => BookStartType::BOOK_START_RESULT_ERROR,
                'bookResult' => BookType::BOOK_RESULT_NOT_BOOKED,
                'bookErrorCode' => $error,
                'bookData' => [],
                'serviceId' => $params['serviceId'],
            ];
        } else {
            if (!isset($response['body'])) {
                throw new KmpException(
                    get_class(), __FUNCTION__,
                    OrdersErrors::INCORRECT_BOOK_RESULT,
                    ['response' => $response]
                );
            }
            $response = $response['body'];
        }

        /*
        $response = [
            "serviceId" => 17503,
            "bookStartResult" => 0,
            "bookErrorCode" => 0,
            'bookResult' => 0,
            "bookData" =>
                [
                    "pnrData" =>
                        [
                            "engine" =>
                                [
                                    "type" => 5,
                                    "GPTS_service_ref" => "/JBPM/agent.productType.flight/5522902",
                                    "GPTS_order_ref" => 11994,
                                ],
                            "supplierCode" => "",
                            "PNR" => "367KUS"
                        ],
                    "segments" => []
                ],
        ];
        */

        if ($response['bookStartResult'] == BookStartType::BOOK_START_RESULT_RUN) {
            $service->status = ServicesForm::SERVICE_STATUS_W_BOOKED;
            $service->save();
        }

        return $response;
    }

    /**
     * Выполнение необходимых операций с услугой при завершении бронирования
     * @param $params
     * @return array|bool
     */
    public function bookComplete($params)
    {
        $serviceInfo = ServicesForm::getServiceById($params['actionParams']['serviceId']);

        if ($params['actionParams']['bookResult'] == BookType::BOOK_RESULT_NOT_BOOKED) {

            $service = ServicesFactory::createService($serviceInfo['ServiceType']);
            $service->load($params['actionParams']['serviceId']);
            $service->status = ServicesForm::SERVICE_STATUS_MANUAL;
            $service->save();
            return ['serviceStatus' => $service->status];
        }

        $offer = OffersFactory::createOffer($serviceInfo['ServiceType']);

        $offer->load($serviceInfo['OfferID']);

        // допишем lastTicketingDate
        if (!empty($params['actionParams']['bookData']['lastTicketingDate'])) {
            $offer->offerData['lastTicketingDate'] = $params['actionParams']['bookData']['lastTicketingDate'];
            $offer->save();
        }

        $pnr = new ServiceFlPnr();

        $pnr->setAttributes([
            'pnr' => $params['actionParams']['bookData']['pnrData']['PNR'],
            'offerId' => $offer->offerId,
            'supplierCode' => 0,
            'offerKey' => $offer['offerData']['offerKey'],
            'gateId' => $params['actionParams']['bookData']['pnrData']['engine']['type'],
            'serviceRef' => $params['actionParams']['bookData']['pnrData']['engine']['GPTS_service_ref'],
            'orderRef' => $params['actionParams']['bookData']['pnrData']['engine']['GPTS_order_ref'],
            'baggageData' => $params['actionParams']['bookData']['pnrData']['baggage'],
            'status' => 1,
        ]);

        try {
            $pnr->save();
            $service = ServicesFactory::createService($serviceInfo['ServiceType']);
            $service->load($params['actionParams']['serviceId']);
            $service->status = ServicesForm::SERVICE_STATUS_BOOKED;
            $service->serviceGptsId = $params['actionParams']['gateServiceId'];
            $service->save();
        } catch (KmpDbException $kde) {

            LogHelper::logExt(
                $kde->class,
                $kde->method,
                $this->module->getCxtName($kde->class, $kde->method),
                $this->module->getError($kde->getCode()),
                $kde->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );

            $this->errorCode = $kde->getCode();
            return false;
        }
        return true;
    }

    /**
     * Завершающее действие для установки статуса услуги
     * @param $params
     * @return array|bool
     */
    public function payStartPostAction($params)
    {
        $service = new Service();
        $service->load($params['serviceId']);

        switch ($service->status) {
            case ServicesForm::SERVICE_STATUS_BOOKED :
                $service->status = ServicesForm::SERVICE_STATUS_W_PAID;
                break;
            case ServicesForm::SERVICE_STATUS_PAID:
                $service->status = ServicesForm::SERVICE_STATUS_W_PAID;
                break;
        }

        $service->save();
        return ['serviceStatus' => $service->status];
    }

    /**
     * Завершающее действие при формировании счёта в УТК
     * @param $params
     * @return array
     */
    public function payFinishPostAction($params)
    {
        $service = new Service();
        $service->load($params['serviceId']);

        switch ($params['actionParams']['servicePaid']) {
            case InvoiceForm::STATUS_PAYED_COMPLETELY :
                $service->status = ServicesForm::SERVICE_STATUS_PAID;
                break;
            case InvoiceForm::STATUS_PAYED_PARTIAL :
                $service->status = ServicesForm::SERVICE_STATUS_P_PAID;
                break;
            default:
                $service->status = ServicesForm::SERVICE_STATUS_BOOKED;
                break;
        }

        $service->save();
        return ['serviceStatus' => $service->status];
    }

    /**
     * Выполнение операций для выписки
     * @param $params
     * @return array
     */
    public function issueTickets($params)
    {
        $service = new Service();
        $service->load($params['serviceId']);

        $svcBookingParams['usertoken'] = $params['usertoken'];
        $svcBookingParams['serviceType'] = $service->serviceType;

        switch ($service->serviceType) {
            case ServicesFactory::FLIGHT_SERVICE_TYPE :

                $offer = new FlightOffer();
                $offer->load($service->offerId);

                $pnr = new ServiceFlPnr();
                $pnr->loadByOfferId($offer->offerId);

                $svcBookingParams['bookData']['pnrData'] = [
                    'engine' => [
                        'type' => $pnr->gateId,
                        'GPTS_service_ref' => $pnr->serviceRef,
                        'GPTS_order_ref' => $pnr->orderRef
                    ],
                    'supplierCode' => $pnr->supplierCode,
                    'PNR' => $pnr->pnr
                ];

                break;
        }

        $apiClient = new ApiClient($this->module);

        $response = json_decode($apiClient->makeRestRequest('supplierService', 'IssueTickets', $svcBookingParams), true);

        if (RestException::isArrayRestException($response)) {

            $error = SupplierServiceHelper::translateSupplierSvcErrorId($response['errorCode']);

            $this->errorCode = $error;

            $service->status = ServicesForm::SERVICE_STATUS_MANUAL;
            $service->save();

            return false;

        } else {
            $response = $response['body'][0];
        }

        $touristsInfo = TouristForm::getServiceTourists($params['serviceId']);
        $tourists = [];
        foreach ($touristsInfo as $touristInfo) {

            $tourist = new TouristForm($this->namespace);
            $tourist->loadTouristByID($touristInfo['TouristID']);
            $tourists[] = $tourist;
        }

        if (empty($response['tickets'])) {

            throw new KmpInvalidArgumentException(
                get_class($this),
                __FUNCTION__,
                OrdersErrors::INCORRECT_TICKETS_DATA,
                ['SupplierSvcResponse' => $response]
            );
        }

        foreach ($response['tickets'] as $ticketInfo) {

            if (empty($ticketInfo) || empty($ticketInfo['ticket'])) {
                throw new KmpInvalidArgumentException(
                    get_class($this),
                    __FUNCTION__,
                    OrdersErrors::INCORRECT_TICKETS_DATA,
                    ['ticket' => $ticketInfo]
                );
            }
            $ticket = $ticketInfo['ticket'];

            $traveler = [
                'maleFemale' => isset($ticket['traveler']['maleFemale']) ? $ticket['traveler']['maleFemale'] : '',
                'dateOfBirth' => isset($ticket['traveler']['dateOfBirth'])
                    ? preg_replace('/\s\d\d:\d\d/', '', $ticket['traveler']['dateOfBirth'])
                    : '',
                'passportNumber' => isset($ticket['traveler']['passport']['number'])
                    ? $ticket['traveler']['passport']['number']
                    : '',
                'lastName' => isset($ticket['traveler']['lastName']) ? $ticket['traveler']['lastName'] : ''
            ];

            $touristId = false;
            foreach ($tourists as $tourist) {

                $passportNum = $tourist->touristDoc->docSerial . $tourist->touristDoc->docNumber;

                if (intval($tourist->touristBase->sex) == intval($traveler['maleFemale']) &&
                    strcmp(trim($tourist->touristBase->birthDate), trim($traveler['dateOfBirth'])) == 0 &&
                    strcmp(trim($passportNum), trim($traveler['passportNumber'])) == 0 &&
                    strcmp(trim($tourist->touristDoc->surname), trim($traveler['lastName']) == 0)
                ) {
                    $touristId = $tourist->touristId;
                } else {
                    continue;
                }

            }

            if ($touristId != false) {
                $flTicket = new ServiceFlTicket();
                $flTicket->setAttributes([
                        'pnr' => $response['pnrData']['PNR'],
                        'ticketNumber' => $ticket['ticketNumber'],
                        'attachedFormId' => '',
                        'serviceId' => $params['serviceId'],
                        'touristId' => $touristId,
                        'status' => ServiceFlTicket::STATUS_ISSUED
                    ]
                );

                $flTicket->save();
            }
        }
        return true;
    }

    /**
     * запуск указанного делегата
     * @param $delegateName
     * @param $params
     */
    public function runDelegate($delegateType, $params)
    {
        $delegate = WorkflowDelegatesFactory::createDelegate($delegateType);

        if (!$delegate) {
            throw new KmpException(
                get_class($this), __FUNCTION__,
                OrdersErrors::INCORRECT_WORKFLOW_DELEGATE,
                [
                    'delegate' => $delegateType,
                    'params' => $params
                ]
            );
        }

        return $delegate->run($params, $this->module);
    }

    /**
     * Получение названия action по его идентифкатору
     * @param $action
     */
    public static function getActionNameById($action)
    {
        if (!array_key_exists($action, self::$actions)) {
            return false;
        }
        return self::$actions[$action];
    }

    /**
     * Проверка существования указанного action
     * @param $action
     */
    public static function getActionIdByName($action)
    {
        return array_search($action, self::$actions);;
    }

    /**
     * Проверка существования указанного action
     * @param $action
     */
    public static function isActionExists($action)
    {
        if (!in_array($action, self::$actions)) {
            return false;
        }

        return true;
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
     * Получение информации о предложениях поставщика для каждой услуги для указанной заявки
     * @param $orderId
     */
    private function getSupplierServicesInfo($orderId)
    {
        $servicesInfo = OrderSearchForm::getOrdersServices([$orderId]);

        if (empty($servicesInfo)) {
            return false;
        }

        $engineData = [];

        foreach ($servicesInfo as $serviceInfo) {

            if (empty($serviceInfo['offerId'])) {
                continue;
            }

            switch ($serviceInfo['serviceType']) {

                case OffersFactory::FLIGHT_OFFER_TYPE :
                    $pnr = new ServiceFlPnr();
                    $pnr->loadByOfferId($serviceInfo['offerId']);

                    if (empty($pnr->pnr)) {
                        break;
                    }

                    $engineData[] = [
                        'reservationId' => $pnr->pnr,
                        'serviceType' => $serviceInfo['serviceType'],
                        'gatewayId' => $pnr->gateId,
                        'GPTS_service_ref' => $pnr->serviceRef,
                        'GPTS_order_ref' => $pnr->orderRef,
                        'GPTS_processId' => ''
                    ];

                    break;
            }
        }

        return !empty($engineData) ? $engineData : false;
    }
}
