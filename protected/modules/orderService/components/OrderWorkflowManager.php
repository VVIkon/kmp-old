<?php

/**
 * Class OrderWorkflowManager
 * Класс для проверки корректности параметров и условий
 * при перводе заявки из одного сотояния в другое
 */
class OrderWorkflowManager extends Validator
{
    const ACTION_NEW = 1;
    const ACTION_ADD_SERVICE = 2;
    const ACTION_BOOK_START = 3;
    const ACTION_BOOK_COMPLETE = 4;
    const ACTION_PAY_START = 5;
    const ACTION_PAY_FINISH = 6;
    const ACTION_ISSUE_TICKETS = 7;
    const ACTION_DONE = 8;
    const SERVICE_CHECK_STATUS = 9;

    /** @var int Код ошибки */
    private $errorCode;

    /** @var Используется для хранения ссылки на модуль работы с утк */
    private $_utkModule;

    /** @var Используется для хранения ссылки на модуль работы с заявками */
    private $_orderModule;

    /**  @var string namespace для записи логов */
    private $namespace;

    /** @var object ServiceWorkflowManager  Менеджер услуг */
    private $swMgr;

    /** @var array Контекст данных (временное решение) */
    private $dataContext;

    /** @var array  Доступные команды для выполнения по запросу */
    private static $actions = [
        self::ACTION_NEW => 'New',
        self::ACTION_ADD_SERVICE => 'AddService',
        self::ACTION_BOOK_START => 'BookStart',
        self::ACTION_BOOK_COMPLETE => 'BookComplete',
        self::ACTION_PAY_START => 'PayStart',
        self::ACTION_PAY_FINISH => 'PayFinish',
        self::ACTION_ISSUE_TICKETS => 'IssueTickets',
        self::ACTION_DONE => 'Done',
        self::SERVICE_CHECK_STATUS => 'ServiceCheckStatus'
    ];

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
     * Конструктор класса
     * @param $module object
     */
    public function __construct()
    {

        $this->namespace = "system.orderservice";
        $this->_utkModule = YII::app()->getModule('orderService')->getModule('utk');
        $this->_orderModule = YII::app()->getModule('orderService')->getModule('order');
        $this->module = YII::app()->getModule('orderService');
        $this->swMgr = new ServiceWorkflowManager();
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
                case self::ACTION_NEW;
                    /** @todo выпилено т.к. никому ничего не дает сделать
                     * if (!$this->checkActionRights(self::ACTION_NEW, $userToken)) {
                     * return false;
                     * }
                     */

                    if (!$this->checkNew($params['actionParams'])) {
                        return false;
                    }

                    $orderd = $this->newOrder($params['actionParams']);

                    if (!$orderd) {
                        return false;
                    }

                    return ['orderId' => $orderd];
                    break;
                case self::ACTION_ADD_SERVICE;
                    /* @todo выпилено т.к. никому ничего не дает сделать
                     * if (!$this->checkActionRights(self::ACTION_ADD_SERVICE, $userToken)) {
                     * return false;
                     * }
                     */

                    if (!$this->checkAddService($params['actionParams'])) {
                        return false;
                    }

                    /** @todo команда должна возвращать id заявки и id услуги
                     * if (!$this->AddService($params)) {
                     * return false;
                     * }
                     */

                    $result = $this->AddService($params);

                    $this->runDelegate(
                        WorkflowDelegatesFactory::UPDATE_ORDER_IN_UTK,
                        [
                            'module' => $this->_utkModule,
                            'orderModule' => $this->_orderModule,
                            'orderId' => empty($result['orderId']) ? '' : $result['orderId']
                        ]
                    );

                    return $result;

                    break;
                case self::ACTION_BOOK_START;

                    /* @todo требуется реализация
                     * if (!$this->checkActionRights(self::ACTION_BOOK_START, $userToken)) {
                     * return false;
                     * }
                     */

                    if (!$this->checkBookStart([
                        'agreementSet' => $params['actionParams']['agreementSet'],
                        'orderId' => $params['orderId']
                    ])
                    ) {
                        return false;
                    }

                    $result = $this->bookStart($params);
                    $this->runDelegate(
                        WorkflowDelegatesFactory::UPDATE_ORDER_IN_UTK,
                        [
                            'module' => $this->_utkModule,
                            'orderModule' => $this->_orderModule,
                            'orderId' => $params['orderId']
                        ]
                    );

                    return $result;

                    break;
                case self::ACTION_BOOK_COMPLETE;
                    if (!$this->checkBookComplete($params)) {
                        return false;
                    }

                    $result = $this->bookComplete($params);
                    $this->runDelegate(
                        WorkflowDelegatesFactory::UPDATE_ORDER_IN_UTK,
                        [
                            'module' => $this->_utkModule,
                            'orderModule' => $this->_orderModule,
                            'orderId' => $params['orderId']
                        ]
                    );

                    return $result;

                    break;
                case self::ACTION_PAY_START;

                    /* @todo требуется реализация
                     * if (!$this->checkActionRights(self::ACTION_PAY_START, $userToken)) {
                     * return false;
                     * }
                     */

                    $invoiceInfo = $this->runDelegate(
                        WorkflowDelegatesFactory::CREATE_INVOICE_DELEGATE,
                        [
                            'orderModule' => $this->_orderModule,
                            "orderId" => $params['orderId'],
                            /* todo добавить paymenttype для обработки счёта */
                            /*"paymentType" => ,*/
                            "currency" => $params['actionParams']['currency'],
                            "services" => $params['actionParams']['services'],
                        ]
                    );

                    $params['actionParams']['invoiceId'] = $invoiceInfo['invoiceParams']['invoiceId'];

                    if (!$this->checkPayStart($params)) {
                        return false;
                    }

                    $result = $this->payStart($params);

                    $this->runDelegate(
                        WorkflowDelegatesFactory::SEND_REQUEST_INVOICE_TO_UTK_DELEGATE,
                        [
                            'utkModule' => $this->_utkModule,
                            'invoiceParams' => $invoiceInfo['invoiceParams']
                        ]
                    );

                    $this->runDelegate(
                        WorkflowDelegatesFactory::UPDATE_ORDER_IN_UTK,
                        [
                            'module' => $this->_utkModule,
                            'orderModule' => $this->_orderModule,
                            'orderId' => $params['orderId']
                        ]
                    );

                    return $result;

                    break;
                case self::ACTION_PAY_FINISH;

                    /* @todo требуется реализация
                     * if (!$this->checkActionRights(self::ACTION_PAY_FINISH, $userToken)) {
                     * return false;
                     * }
                     */

                    if (!$this->checkPayFinish($params)) {
                        return false;
                    }

                    $result = $this->payFinish($params);

                    $this->runDelegate(
                        WorkflowDelegatesFactory::UPDATE_ORDER_IN_UTK,
                        [
                            'module' => $this->_utkModule,
                            'orderModule' => $this->_orderModule,
                            'orderId' => $params['orderId']
                        ]
                    );

                    return $result;

                    break;
                case self::ACTION_ISSUE_TICKETS;

                    /* @todo требуется реализация
                     * if (!$this->checkActionRights(self::ACTION_ISSUE_TICKETS, $userToken)) {
                     * return false;
                     * }
                     */

                    if (!$this->checkIssueTickets($params)) {
                        return false;
                    }

                    if (!$this->issueTickets($params)) {
                        return false;
                    }

                    $result = $this->runDelegate(
                        WorkflowDelegatesFactory::RUN_OWM_COMMAND_DELEGATE,
                        [
                            'action' => self::getActionNameById(self::ACTION_DONE),
                            'orderId' => $params['orderId'],
                            'actionParams' => [
                                'serviceId' => $params['actionParams']['serviceId']
                            ],
                            'usertoken' => $params['usertoken']
                        ]
                    );

                    $this->runDelegate(
                        WorkflowDelegatesFactory::UPDATE_ORDER_IN_UTK,
                        [
                            'module' => $this->_utkModule,
                            'orderModule' => $this->_orderModule,
                            'orderId' => $params['orderId']
                        ]
                    );

                    return $result;

                    break;
                case self::ACTION_DONE;

                    /* @todo требуется реализация
                     * if (!$this->checkActionRights(self::ACTION_DONE, $userToken)) {
                     * return false;
                     * }
                     */
                    /* todo раскомментировать после реализации обработки счетов */
                    /*if (!$this->checkDone($params)) {
                        return false;
                    }*/

                    $result = $this->done($params);
                    $this->runDelegate(
                        WorkflowDelegatesFactory::UPDATE_ORDER_IN_UTK,
                        [
                            'module' => $this->_utkModule,
                            'orderModule' => $this->_orderModule,
                            'orderId' => $params['orderId']
                        ]
                    );

                    return $result;

                    break;
                case self::SERVICE_CHECK_STATUS;

                    $result = $this->runDelegate(
                        WorkflowDelegatesFactory::RUN_OWM_COMMAND_DELEGATE,
                        [
                            'action' => self::getActionNameById(self::ACTION_DONE),
                            'orderId' => $params['orderId'],
                            'actionParams' => [
                                'serviceId' => $params['actionParams']['serviceId']
                            ],
                            'usertoken' => $params['usertoken']
                        ]
                    );

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
        } catch (KmpException $ke) {
            LogHelper::logExt(
                $ke->class,
                $ke->method,
                $this->module->getCxtName($ke->class, $ke->method),
                $this->module->getError($ke->getCode()),
                $ke->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $ke->getCode();
            return false;
        }

        return true;
    }

    /**
     * Валидация параметров запуска действия
     * @param $params
     */
    public function checkRunAction($params)
    {

        $actionValidator = new OwmActionsValidator($this->module);

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

        } catch (KmpInvalidArgumentException $kse) {

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
     * Выполнение обработки команды PayStart
     * @param $params
     */
    public function payStart($params)
    {
        $ordersValidator = $this->_orderModule->OrdersValidator($this->_orderModule);
        $needToIssueDoc = $ordersValidator->checkNotIssuedAdditionalServices($params['orderId']);

        $serviceIds = InvoiceServiceForm::getInvoiceServicesIds($params['actionParams']['invoiceId']);

        $sWMParams = $params;
        $sWMParams['action'] = $params['action'] = ServiceWorkflowManager
            ::getActionNameById(ServiceWorkflowManager::ACTION_PAY_START);

        $servicesStatuses = [];
        foreach ($serviceIds as $serviceId) {
            $sWMParams['serviceId'] = $serviceId;

            $result = $this->swMgr->runAction($sWMParams);

            if (!$result) {
                $this->errorCode = $this->swMgr->getLastError();
                return false;
            }

            $this->setOrderAggregateStatus($params['orderId']);

            if (isset($result['serviceStatus'])) {
                $servicesStatuses[$serviceId] = $result['serviceStatus'];
            }
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderInfo = $orderForm->getOrderById($params['orderId']);

        return ['orderStatus' => $orderInfo['Status'], 'servicesStatuses' => $servicesStatuses];
    }

    /**
     * Выполнение обработки команды PayFinish
     * @param $params
     * @return array|bool
     */
    public function payFinish($params)
    {
        $swmParams = $params;
        $swmParams['action'] = $params['action'] = ServiceWorkflowManager
            ::getActionNameById(ServiceWorkflowManager::ACTION_PAY_FINISH);

        foreach ($params['actionParams']['services'] as $invoiceService) {

            $swmParams['serviceId'] = $invoiceService['serviceId'];
            $swmParams['actionParams'] = ['servicePaid' => $invoiceService['servicePaid']];

            $result = $this->swMgr->runAction($swmParams);

            if (!$result) {
                $this->errorCode = $this->swMgr->getLastError();
                return false;
            }

            if (isset($result['serviceStatus']) && $result['serviceStatus'] == ServicesForm::SERVICE_STATUS_PAID) {

                $token = ServiceUser::getToken();
                if (empty($token)) {
                    throw new KmpInvalidSettingsException(
                        get_class($this),
                        __FUNCTION__,
                        OrdersErrors::SERVICE_USER_TOKEN_NOT_FOUND,
                        ['userId' => ServiceUser::SERVICE_USER_ID]
                    );
                }

                $AsyncTask = new AsyncTask();
                $AsyncTask->setModule(Yii::app()->getModule('orderService'));
                $AsyncTask->setTaskParams('orderService', 'OrderWorkflowManager', [
                    'orderId' => $params['orderId'],
                    'action' => 'IssueTickets',
                    'actionParams' => [
                        'serviceId' => $swmParams['serviceId']
                    ],
                    'usertoken' => $token
                ]);
                $AsyncTask->run();

//                $owmParams['orderId'] = $params['orderId'];
//                $owmParams['action'] = 'IssueTickets';
//                $owmParams['actionParams'] = [
//                    'serviceId' => $swmParams['serviceId'],
//                    'agreementSet' => 1
//                ];
//                $owmParams['usertoken'] = $token;
//
//                $result = $this->runAction($owmParams);

//                if ($result) {
                    $this->setOrderAggregateStatus($params['orderId']);
//                } else {
//                    $orderForm = OrderForm::createInstance($this->namespace);
//                    $orderInfo = $orderForm->getOrderById($params['orderId']);
//                    $orderInfo['Status'] = OrderForm::ORDER_STATUS_MANUAL;
//                    $order = $orderForm->orderfromDbData($orderInfo);
//                    $order->updateOrder();
//                }
            }
        }
        return true;
    }

    /**
     * Выполнение обработки команды IssueTickets
     * @param $params
     * @return array|bool
     */
    public function issueTickets($params)
    {
        $swmParams = $params;
        $swmParams['action'] = ServiceWorkflowManager
            ::getActionNameById(ServiceWorkflowManager::ACTION_ISSUE_TICKETS);

        $swmParams['serviceId'] = $params['actionParams']['serviceId'];
        $swmParams['actionParams'] = ['callSource' => ServiceWorkflowManager::CALLED_FROM_OWM];

        $result = $this->swMgr->runAction($swmParams);

        if (!$result) {
            $this->errorCode = $this->swMgr->getLastError();
            return false;
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderInfo = $orderForm->getOrderById($params['orderId']);

        $service = new Service();
        $service->load($params['actionParams']['serviceId']);

        return ['orderStatus' => $orderInfo['Status'], 'serviceStatus' => $service->status];
    }

    /**
     * Выполнение завершения обработки заявки
     * @param $params
     * @return bool
     */
    public function done($params)
    {
        $swmParams = $params;
        $swmParams['action'] = ServiceWorkflowManager
            ::getActionNameById(ServiceWorkflowManager::ACTION_DONE);

        $swmParams['serviceId'] = $params['actionParams']['serviceId'];
        $swmParams['actionParams'] = []; //['callSource' => ServiceWorkflowManager::CALLED_FROM_OWM];

        $result = $this->swMgr->runAction($swmParams);

        if (!$result) {
            $this->errorCode = $this->swMgr->getLastError();
            return false;
        }

        $orderInfo = OrderForm::getOrderByServiceId($params['actionParams']['serviceId']);
        $order = OrderForm::createInstance($this->namespace)->orderfromDbData($orderInfo);
        $servicesInfo = OrderSearchForm::createInstance()->getOrdersServices($order->orderId);

        $canSetStatusDone = true;
        foreach ($servicesInfo as $serviceInfo) {

            if ($serviceInfo['status'] != ServicesForm::SERVICE_STATUS_DONE) {
                $canSetStatusDone = false;
                break;
            }

            $serviceSum = InvoiceServiceForm::getServicePaidSum($serviceInfo['serviceID']);

            if ($serviceSum != $serviceInfo['kmpPrice']) {
                $canSetStatusDone = false;
                break;
            }
        }

        if ($canSetStatusDone) {
            $order->status = OrderForm::ORDER_STATUS_DONE;
            $order->updateOrder();
        }

        return true;
    }

    /**
     * Установка скидки агентства
     * @param $params
     * @return bool
     */
    public function setDiscount($params)
    {
        if (!$this->checkSetDiscount($params)) {
            return false;
        }

        $orderSearchForm = OrderSearchForm::createInstance();
        $orderSearchForm->orderId = $params['orderId'];
        $order = $orderSearchForm->getOrder();

        if (!$order) {
            $this->errorCode = OrdersErrors::ORDER_NOT_FOUND;
            return false;
        }

        $services = $orderSearchForm->getOrdersServices($order['orderId']);

        if (empty($services) || count($services) == 0) {
            $this->errorCode = OrdersErrors::NO_SERVICES_IN_ORDER;
            return false;
        }

        $ordersMgr = $this->_orderModule->OrdersMgr($this->_orderModule);

        $commission = $ordersMgr->getOrderContractCommission($order['orderId']);

        if (!$commission) {
            $this->errorCode = $ordersMgr->getLastError();
            return false;
        }

        $agency = new AgentForm($this->namespace);
        $agencyContractCommission = $agency->getAgencyContractCommission($order['agentId']);
        if (!$agencyContractCommission) {
            $this->errorCode = OrdersErrors::CANNOT_GET_AGENCY_COMMISSION;
            return false;
        }


        if ($params['agentOrderDiscount'] > $commission) {
            $this->errorCode = OrdersErrors::DISCOUNT_SUM_BIGGER_THAN_AGENT_COMMISSION;
            return false;
        }

        $commissionByPercent = ($commission - $params['agentOrderDiscount'])
            / floatval($commission / $agencyContractCommission);

        $result = $ordersMgr->setOrderServicesAgencyCommisssion($order['orderId'], $commissionByPercent);

        if (!$result) {
            $this->errorCode = $ordersMgr->getLastError();
            return false;
        }

        return $result;
    }

    /**
     * Удалить туриста из заявки
     * @param $params
     * @return bool
     */
    public function removeTourist($params)
    {

        $cxtName = $this->module->getCxtName(get_class($this), __FUNCTION__);
        $err = $this->module;

        $ordersMgr = $this->_orderModule->OrdersMgr($this->_orderModule);

        if (!$this->checkRemoveTouristConditions($params)) {
            $errDesc = $err->getError($this->errorCode);
            LogHelper::logExt(get_class(), __FUNCTION__,
                'removeTourist', $errDesc, $params,
                'error', 'system.orderservice.error');
            return false;
        }
        $result = $ordersMgr->removeTouristFromOrder($params);

        if (!$result) {
            $this->errorCode = $ordersMgr->getLastError();
            $errDesc = $err->getError($this->errorCode);
            LogHelper::logExt(get_class(), __FUNCTION__,
                'removeTourist', $errDesc, $params,
                'error', 'system.orderservice.error');
            return false;
        }
        LogHelper::logExt(get_class($this), __METHOD__,
            'removeTourist', json_encode($params), $result,
            'info', 'system.orderservice.info');
        return $result;
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
                case self::ACTION_NEW :
                    $this->checkRights([
                        'OR' => [
                            RightsRegister::RIGHT_ORDERS_CREATE,
                            RightsRegister::RIGHT_OWN_COMPANY_ORDERS_CREATE,
                            RightsRegister::RIGHT_OTHER_COMPANIES_ORDERS_CREATE
                        ]
                    ], $userToken);
                    break;
                case self::ACTION_ADD_SERVICE :
                    $this->checkRights([
                        'OR' => [
                            RightsRegister::RIGHT_ORDERS_CREATE
                        ]
                    ], $userToken);
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
     * Проверка возможности удаления туриста из заявки
     * @param $params
     * @return bool
     */
    public function checkRemoveTouristConditions($params)
    {

        $touristValidator = $this->_orderModule->TouristsValidator($this->_orderModule);

        if (!$touristValidator->checkTouristDeletingParams($params)) {
            $this->errorCode = $touristValidator->getLastError();
            return false;
        }

        if (!$touristValidator->checkTouristInOrder($params)) {
            $this->errorCode = $touristValidator->getLastError();
            return false;
        }

        if (!$this->swMgr->checkCanRemoveTouristFromService($params)) {
            $this->errorCode = $this->swMgr->getLastError();
            return false;
        }

        return true;
    }

    /**
     * Проверка условий для установки скидки агентства
     * @param $params
     */
    private function checkSetDiscount($params)
    {

        $ordersValidator = $this->_orderModule->OrdersValidator($this->_orderModule);

        if (!$ordersValidator->checkSetDiscountParams($params)) {
            $this->errorCode = $ordersValidator->getLastError();
            return false;
        }

        return true;
    }

    /**
     * Проверка предусловия для выставления счёта
     * @param $orderId
     * @return bool
     */
    private function checkPayStart($params)
    {

        $ordersValidator = $this->_orderModule->OrdersValidator($this->_orderModule);

        $params['actionParams']['orderId'] = isset($params['orderId'])
            ? $params['orderId']
            : '';

        try {
            $ordersValidator->checkPayStartParams($params['actionParams']);
        } catch (KmpInvalidArgumentException $kae) {
            LogExceptionsHelper::logExceptionEr($kae, $this->module, $this->namespace . '.errors');

            $this->errorCode = $kae->getCode();
            return false;
        }

        return true;
    }

    /**
     * Проверка параметров выполнения команды PayFinish
     * @param $params
     * @return bool
     */
    private function checkPayFinish($params)
    {

        $ordersValidator = $this->_orderModule->OrdersValidator($this->_orderModule);

        $params['actionParams']['orderId'] = isset($params['orderId'])
            ? $params['orderId']
            : '';

        try {
            $ordersValidator->checkPayFinishParams($params['actionParams']);
        } catch (KmpInvalidArgumentException $kae) {
            LogExceptionsHelper::logExceptionEr($kae, $this->module, $this->namespace . '.errors');
            $this->errorCode = $kae->getCode();
            return false;
        }

        return true;
    }

    /**
     * Проверка параметров команды инициации выписки билет
     * @param $params
     * @return bool
     */
    private function checkIssueTickets($params)
    {

        $ordersValidator = $this->_orderModule->OrdersValidator($this->_orderModule);

        $params['actionParams']['orderId'] = isset($params['orderId'])
            ? $params['orderId']
            : '';

        try {
            $ordersValidator->checkIssueTicketsParams($params['actionParams']);
        } catch (KmpInvalidArgumentException $kae) {
            LogExceptionsHelper::logExceptionEr($kae, $this->module, $this->namespace . '.errors');
            $this->errorCode = $kae->getCode();
            return false;
        }

        return true;
    }

    /**
     * Проверка параметров команды завершения обработки заявки
     * @param $params array
     * @return bool
     */
    private function checkDone($params)
    {

        $ordersValidator = $this->_orderModule->OrdersValidator($this->_orderModule);

        try {
            $ordersValidator->checkDoneParams($params);
        } catch (KmpInvalidArgumentException $kae) {
            LogExceptionsHelper::logExceptionEr($kae, $this->module, $this->namespace . '.errors');
            $this->errorCode = $kae->getCode();
            return false;
        }

        return true;
    }


    private function checkNew($params)
    {
        $ordersValidator = $this->_orderModule->OrdersValidator($this->_orderModule);

        try {
            $ordersValidator->checkNewOrderCommonParams($params);
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
        }
        return true;
    }

    /**
     * Проверка параметров команды на добавление услуги
     * @param mixed[] $params - структура actionParams
     */
    private function checkAddService($params)
    {
        $ordersValidator = $this->_orderModule->OrdersValidator($this->_orderModule);
        $ordersValidator->checkAddServiceCommonParams($params);
        return true;
    }

    /**
     * Проверка параметров команды на запуск процесса бронирования
     * @param $params $params - структура actionParams
     */
    private function checkBookStart($params)
    {
        $ordersValidator = $this->_orderModule->OrdersValidator($this->_orderModule);
        $ordersValidator->checkBookStartCommonParams($params);
        return true;
    }

    /**
     * Проверка параметров команды на установку параметров бронирования услуги
     * @param $params array - структура actionParams
     */
    private function checkBookComplete($params)
    {
        $ordersValidator = $this->_orderModule->OrdersValidator($this->_orderModule);

        if (isset($params['orderId'])) {
            $params['actionParams']['orderId'] = $params['orderId'];
        }
//        try {
//            $ordersValidator->checkBookCompleteParams($params['actionParams']);
//        } catch (Exception $e) {
//            var_dump($e);
//            exit;
//        }

        return true;
    }

    /**
     * Команда добавления услуги в завявку
     * @param mixed[] $param - все параметры команды
     * @return mixed[]|false - параметры ответа или false в случае ошибки
     */
    private function addService($params)
    {
        if (empty($params['orderId'])) {

            $orderNewParams = [];

            $apiClient = new ApiClient($this->module);
            $profile = $apiClient->getUserProfileByToken($params['usertoken']);

            $agentId = $profile['companyID'];
            $companyManagerId = $profile['userId'];

            $agencyForm = new AgentForm($this->namespace);
            $contractId = $agencyForm->getActiveAgencyContractId($profile['companyID']);

            if (empty($contractId)) {
                throw new KmpInvalidSettingsException(
                    get_class($this),
                    __FUNCTION__,
                    OrdersErrors::AGENT_NOT_ACTIVE,
                    ['companyId' => $profile['companyID']]
                );
            }
            /** @todo не получается получить менеджера */
            $kmpManagerId = ResponsibleManager::getManagerIdByCompanyId($profile['companyID']);

            if (empty($kmpManagerId)) {
                throw new KmpInvalidSettingsException(
                    get_class($this),
                    __FUNCTION__,
                    OrdersErrors::CANNOT_GET_RESPONSIBLE_MANAGER,
                    ['companyId' => $profile['companyID']]
                );
            }

            $orderNewParams['actionParams'] = [
                'agentId' => $agentId,
                'companyManagerId' => $companyManagerId,
                'contractId' => $contractId,
                'kmpManagerId' => $kmpManagerId
            ];

            $orderNewParams['action'] = self::getActionNameById(self::ACTION_NEW); //['orderId'];
            $orderNewParams['orderId'] = '';
            $orderNewParams['usertoken'] = $params['usertoken'];

            /** @todo временно отключено чтобы не плодить мусор */
            $order = $this->runAction($orderNewParams);

            if (!$order) {
                return ['msg' => 'no order'];
                /** @TODO for debug */
            }

            $params['orderId'] = $order['orderId'];
        }
        /** Здесь должна вызываться проверка статуса заявки (согласно валидации OWM AddService) */

        $params['action'] = ServiceWorkflowManager
            ::getActionNameById(ServiceWorkflowManager::ACTION_SERVICE_CREATE);
        //$params['serviceId'] = ''; // зачем тут это -?

        $serviceId = $this->swMgr->runAction($params);

        $this->setOrderAggregateStatus($params['orderId']);

        return [
            'orderId' => $params['orderId'],
            'serviceId' => $serviceId
        ];
    }

    /**
     * Бронирование услуги в заявке
     * @param $params
     * @return array|bool
     */
    private function bookStart($params)
    {
        $params['action'] = ServiceWorkflowManager
            ::getActionNameById(ServiceWorkflowManager::ACTION_BOOK_START);

        if (isset($params['actionParams']['serviceId'])) {
            $params['serviceId'] = $params['actionParams']['serviceId'];
        }

        $OrderModel = OrderModelRepository::getByOrderId($params['orderId']);

        // костыль - добавляем номер заявки в ГПТС для связи с другими услугами
        $params['gateOrderId'] = '';

        if (!is_null($OrderModel)) {
            $params['gateOrderId'] = $OrderModel->getOrderIDGP();
        }

        $result = $this->swMgr->runAction($params);

//        var_dump($result);
//        exit;

        if (!$result) {
            $this->errorCode = $this->swMgr->getLastError();
            return $result;
        }

        if (isset($result['bookStartResult']) &&
            (
                $result['bookStartResult'] == BookStartType::BOOK_START_RESULT_BOOKED ||
                $result['bookStartResult'] == BookStartType::BOOK_START_RESULT_ERROR
            )
        ) {
            $params = [
                'orderId' => $params['orderId'],
                'usertoken' => $params['usertoken'],
                'action' => OrderWorkflowManager::getActionNameById(OrderWorkflowManager::ACTION_BOOK_COMPLETE),
                'actionParams' => [
                    'bookResult' => $result['bookResult'],
                    'bookData' => $result['bookData'],
                    'serviceId' => $result['serviceId'],
                    'gateServiceId' => isset($result['gateServiceId']) ? $result['gateServiceId'] : null
                ]
            ];
            $this->runAction($params);
        }

        $this->setOrderAggregateStatus($params['orderId']);

        $orderSearchForm = OrderSearchForm::createInstance();
        $orderSearchForm->orderId = $params['orderId'];
        $orderInfo = $orderSearchForm->getOrderById();

        $serviceInfo = ServicesForm::getServiceById($result['serviceId']);

        if (isset($result['bookErrorCode']) && !empty($result['bookErrorCode'])) {
            $this->errorCode = $result['bookErrorCode'];
            return false;
        }

        return ['orderStatus' => $orderInfo['Status'], 'serviceStatus' => $serviceInfo['Status']];
    }

    /**
     * Завершение операции бронирования предложения
     * @param $params
     * @return array|bool
     */
    public function bookComplete($params)
    {
        $params['action'] = ServiceWorkflowManager::getActionNameById(ServiceWorkflowManager::ACTION_BOOK_COMPLETE);

        $result = $this->swMgr->runAction($params);

        if (!$result) {
            $this->errorCode = $this->swMgr->getLastError();
            return false;
        }

        $this->setOrderAggregateStatus($params['orderId']);

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderInfo = $orderForm->getOrderById($params['orderId']);

        LogHelper::logExt(
            __CLASS__,
            __METHOD__,
            'BookData',
            '',
            $params,
            LogHelper::MESSAGE_TYPE_INFO,
            'system.orderservice.info'
        );

        if (isset($params['bookData']['pnrData']['engine']['GPTS_order_ref'])) {
            $orderInfo['OrderID_GP'] = $params['bookData']['pnrData']['engine']['GPTS_order_ref'];
            $order = $orderForm->orderfromDbData($orderInfo);
            $order->updateOrder();
        }
        $serviceInfo = ServicesForm::getServiceById($params['serviceId']);

        return ['orderStatus' => $orderInfo['Status'], 'serviceStatus' => $serviceInfo['Status']];
    }

    /**
     * Создание объекта заявка
     * @param $params array
     */
    public function newOrder($params)
    {
        $order = new Order($this->namespace);

        $order->initParams([
            'orderUtkId' => '',
            'orderIdGpts' => '',
            'orderDate' => (new DateTime('now'))->format('Y-m-d H:i:s'),
            'status' => OrderForm::ORDER_STATUS_NEW,
            'agencyId' => $params['agentId'],
            'agencyUserId' => $params['companyManagerId'],
            'archive' => false,
            'vip' => false,
            'contractId' => $params['contractId'],
            'blocked' => false,
            'comment' => '',
            'companyManagerId' => $params['companyManagerId'],
            'KMPManager' => $params['kmpManagerId'],
        ]);
        try {
            $order->save();
        } catch (KmpDbException $kde) {

            LogHelper::logExt(
                $kde->class,
                $kde->method,
                $this->module->getCxtName($kde->class, $kde->method),
                $this->module->getError($kde->getCode()) . PHP_EOL . $kde->getPrevious()->getMessage(),
                $kde->params,
                LogHelper::MESSAGE_TYPE_ERROR,
                $this->namespace . '.errors'
            );
            $this->errorCode = $kde->getCode();
            return false;
        }

        return $order->orderId;
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
     * Установка агрегатного статуса заявки
     * в зависимости от статуса её услуг
     * @param $orderId int
     * @return bool
     */
    private function setOrderAggregateStatus($orderId)
    {
        if (empty($orderId)) {
            return false;
        }

        $orderForm = OrderForm::createInstance($this->namespace);
        $orderSearchForm = OrderSearchForm::createInstance($this->namespace);
        $order = $orderForm->getOrderByIdObj($orderId);

        $servicesInfo = $orderSearchForm->getOrdersServices($orderId);

        $allServicesHasSameStatus = function ($svcsInfo, array $checkStatus) {
            foreach ($svcsInfo as $svcInfo) {
                if ($svcInfo['status'] != $checkStatus) {
                    return false;
                }
            }
            return true;
        };

        $allServicesInStatuses = function ($svcsInfo, array $checkStatuses) {
            foreach ($svcsInfo as $svcInfo) {
                if (!in_array($svcInfo['status'], $checkStatuses)) {
                    return false;
                }
            }
            return true;
        };

        $serviceHasOneOfStatuses = function ($svcsInfo, array $checkStatuses) {
            foreach ($svcsInfo as $svcInfo) {
                if (in_array($svcInfo['status'], $checkStatuses)) {
                    return true;
                }
            }
            return false;
        };

        if (count($servicesInfo) == 0) {
            $order->status = OrderForm::ORDER_STATUS_NEW;
        }

        if ($allServicesInStatuses($servicesInfo, [
                ServicesForm::SERVICE_STATUS_NEW,
                ServicesForm::SERVICE_STATUS_W_BOOKED,
                ServicesForm::SERVICE_STATUS_BOOKED,
                ServicesForm::SERVICE_STATUS_DONE,
            ]) && $serviceHasOneOfStatuses($servicesInfo, [
                ServicesForm::SERVICE_STATUS_W_BOOKED,
                ServicesForm::SERVICE_STATUS_NEW
            ])
        ) {
            $order->status = OrderForm::ORDER_STATUS_NEW;
        }

        if ($allServicesInStatuses($servicesInfo, [
            ServicesForm::SERVICE_STATUS_BOOKED,
            ServicesForm::SERVICE_STATUS_DONE,
            ServicesForm::SERVICE_STATUS_CANCELLED
        ])
        ) {
            $order->status = OrderForm::ORDER_STATUS_BOOKED;
        }

        if ($allServicesInStatuses($servicesInfo,
                [
                    ServicesForm::SERVICE_STATUS_NEW,
                    ServicesForm::SERVICE_STATUS_W_BOOKED,
                    ServicesForm::SERVICE_STATUS_BOOKED,
                    ServicesForm::SERVICE_STATUS_W_PAID,
                    ServicesForm::SERVICE_STATUS_P_PAID,
                    ServicesForm::SERVICE_STATUS_PAID,
                    ServicesForm::SERVICE_STATUS_DONE
                ]
            ) && $serviceHasOneOfStatuses($servicesInfo, [
                ServicesForm::SERVICE_STATUS_W_PAID,
                ServicesForm::SERVICE_STATUS_P_PAID,
                ServicesForm::SERVICE_STATUS_PAID
            ])
        ) {
            $order->status = OrderForm::ORDER_STATUS_W_PAID;
        }

        if ($allServicesInStatuses($servicesInfo, [
                ServicesForm::SERVICE_STATUS_PAID,
                ServicesForm::SERVICE_STATUS_DONE
            ]) && $serviceHasOneOfStatuses($servicesInfo, [ServicesForm::SERVICE_STATUS_PAID])
        ) {
            $order->status = OrderForm::ORDER_STATUS_PAID;
        }

        if ($allServicesInStatuses($servicesInfo, [
                ServicesForm::SERVICE_STATUS_PAID,
                ServicesForm::SERVICE_STATUS_DONE
            ]) && $serviceHasOneOfStatuses($servicesInfo, [ServicesForm::SERVICE_STATUS_DONE])
        ) {
            $order->status = OrderForm::ORDER_STATUS_DONE;
        }

        if ($allServicesInStatuses($servicesInfo, [
            ServicesForm::SERVICE_STATUS_CANCELLED,
            ServicesForm::SERVICE_STATUS_VOIDED
        ])) {
            $order->status = OrderForm::ORDER_STATUS_ANNULED;
        }

        if ($serviceHasOneOfStatuses($servicesInfo, [
            ServicesForm::SERVICE_STATUS_MANUAL
        ])) {
            $order->status = OrderForm::ORDER_STATUS_MANUAL;
        }

        $order->updateOrder();
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
