<?php

/**
 * Class UtkClientController
 * Используется для реализации клиентских запросов к UTK
 */
class UtkClientController extends SecuredRestController
{

    /**
     * Грязный хак для обхода проверки токена пользователя при вызове getClientsList
     * Нужно для возможности вызова команды из updateDictionary
     * @todo переделать
     */
    protected function beforeAction($action)
    {
        if (strcmp(Yii::app()->controller->action->id, 'GetClientsList') !== 0) {
            parent::beforeAction($action);
            return true;
        } else {
            $this->log(json_encode($this->_getRequestParams(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return true;
        }

    }

    /**
     * Правила доступа
     * @return array
     */
    public function accessRules()
    {
        return array(
            array('allow', 'actions' => array('authenticate'),
                'users' => array('FULL_ACCESS'),
            ),
            array('deny',  // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Получить список идентифкаторов заявок из УТК
     */
    public function actionOrderList()
    {
        set_time_limit(5400);
        $module = YII::app()->getModule('orderService')->getModule('utk');
        $utkClient = $module->UtkOrdersClient($module);
        $utkValidator = $module->UtkRequestValidator($module);

        $params = $this->_getRequestParams();

        $response = $utkValidator->checkUTKOrderListParams($params);

        if (!$response) {

            $this->_sendResponse(false, array(),
                $this->getErrorDescription($utkValidator->getLastError()),
                $utkValidator->getLastError()
            );
        }

        $response = $utkClient->makeOrderListRequest(UtkOrdersClient::REQUEST_ORDER_LIST, $params);

        if ($response) {
            $this->_sendResponse(true, $response, '');
        } else {

            $this->_sendResponse(false, array(),
                $this->getErrorDescription($utkClient->getLastError()),
                $utkClient->getLastError()
            );
        }
    }

    /**
     * Получить полную информацию по заявке из УТК
     */
    public function actionOrderView()
    {
        $module = YII::app()->getModule('orderService')->getModule('utk');
        $utkClient = $module->UtkClient($module);

        $utkValidator = $module->UtkRequestValidator($module);

        $params = $this->_getRequestParams();

        $response = $utkValidator->checkUTKOrderViewParams($params);

        if (!$response) {

            $this->_sendResponse(false, array(),
                $this->getErrorDescription($utkValidator->getLastError()),
                $utkValidator->getLastError()
            );
        }

        $response = $utkClient->makeRestRequest(UtkOrdersClient::REQUEST_ORDER_VIEW, $params);

        if ($response) {
            $this->_sendResponse(true, $response, '');
        } else {

            $this->_sendResponse(false, array(),
                $this->getErrorDescription($utkClient->getLastError()),
                $utkClient->getLastError()
            );
        }
    }

    /**
     * Отправить информацию о заявке в УТК
     */
    public function actionOrderToUTK()
    {
        $module = YII::app()->getModule('orderService')->getModule('utk');
        $orderModule = YII::app()->getModule('orderService')->getModule('order');
        $utkClient = $module->UtkClient($module);

        $params = $this->_getRequestParams();

        $utkMgr = $module->UtkManager($module, $orderModule);

        $response = $utkMgr->exportOrderToUTK($params['orderId']);

        if ($response) {
            $this->_sendResponse(true, $response, '');
        } else {

            $this->_sendResponse(false, array(),
                $this->getErrorDescription($utkMgr->getLastError()),
                $utkMgr->getLastError()
            );
        }
    }

    /**
     * Отправить запрос на создание счёта в УТК
     */
    public function actionSetInvoice()
    {
        $module = YII::app()->getModule('orderService')->getModule('order');

        $params = $this->_getRequestParams();

        $utkValidator = $module->UtkValidator($module);

        if (!$utkValidator->checkSetInvoiceParams($params)) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($utkValidator->getLastError()),
                $utkValidator->getLastError()
            );
        };

//        if (!$utkValidator->checkSetInvoiceConditions($params['orderId'])) {
//            $this->_sendResponse(false, array(),
//                $this->getErrorDescription($utkValidator->getLastError()),
//                $utkValidator->getLastError()
//            );
//        }

//        $module = YII::app()->getModule('orderService');
//        $orderWfMgr = $module->OrderWorkflowManager();


//        $response = $orderWfMgr->runAction($owmParams);

//        if ($response !== false) {
//            $this->_sendResponse(true, $response, '');
//        } else {
//            $this->_sendResponse(false, array(),
//                $this->getErrorDescription($orderWfMgr->getLastError()),
//                $orderWfMgr->getLastError()
//            );
//        }

        $Event = EventRepository::getEventByCommand('PayStart');

        if (is_null($Event)) {
            $this->_sendResponseWithErrorCode(OrdersErrors::INCORRECT_OWM_ACTION);
        }

        // получаем объект Заявки
        $OrderModel = OrderModelRepository::getByOrderId($params['orderId']);

        if (is_null($OrderModel)) {
            $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_NOT_FOUND);
        }

        // инициализируем машину состояний и применяем действие
        $OWM_FSM = new StateMachine($OrderModel);

        if (!$OWM_FSM->can($Event->getEvent())) {
            $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_STATUS_INCORRECT_FOR_ACTION);
        }

        if (!$OWM_FSM->userHasAccess($Event->getEvent())) {
            $this->_sendResponseWithErrorCode(OrdersErrors::NOT_ENOUGH_USER_RIGHTS);
        }

        $owmActionParams = [
            'currency' => $params['currency'],
            'services' => $params['Services'],
            'userProfile' => Yii::app()->user->getState('userProfile'),
            'userPermissions' => Yii::app()->user->getState('userPermissions'),
            'usertoken' => $params['usertoken'],
            'token' => $params['token'],
            'orderId' => $OrderModel->getOrderId()
        ];

        // настройка EventManager
        $EventManager = EventManager::getInstance();
        $EventManager->init(Yii::app()->getModule('orderService')->getConfig('gearman'));

        try {
            $response = $OWM_FSM->apply($Event->getEvent(), $owmActionParams);

            if (empty($response)) {
                $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            }
        } catch (FSMException $e) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Запуск StateMachine', "Ошибка машины состояний {$e->getMessage()}",
                '',
                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.error'
            );
            $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return;
        }

        if ($response['status']) {
            $this->_sendResponseWithErrorCode($response['status']);
        } else {
            $this->_sendResponseData($response['response']);
        }
    }

    /**
     * Функция инициирует отмену счёта (в УТК).
     */
    public function actionSetInvoiceCancel()
    {
        $this->_sendErrorResponseIfNoPermissions([22, 47, 51]);

        $params = $this->_getRequestParams();

        // валидация параметра
        if (!isset($params['invoiceId'])) {
            $this->_sendResponseWithErrorCode(OrdersErrors::INPUT_PARAMS_ERROR);
        }

        // найдем счет с таким ID
        $Invoice = InvoiceRepository::getInvoiceById($params['invoiceId']);

        if (is_null($Invoice)) {
            $this->_sendResponseWithErrorCode(OrdersErrors::INVOICE_NOT_FOUND);
        }

        // отменяем счет
        $Invoice->cancel();

        // сформирует сущность счета УТК
        $UTKInvoice = UTKInvoiceRepository::getByInvoiceId($params['invoiceId']);

        if (is_null($UTKInvoice)) {
            $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
        }

        // отправим в УТК счет
        $UTKInvoiceArray = $UTKInvoice->toArray();

//        var_dump($UTKInvoiceArray);
//        exit;

        $UTKClient = new UtkClient(Yii::app()->getModule('orderService')->getModule('utk'));
        $response = $UTKClient->makeRestRequest('invoice', $UTKInvoiceArray);

        if (!$response) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Отправка отмененного счета в УТК', 'Не удалось отправить счет в УТК',
                $UTKInvoiceArray,
                'error', 'system.orderservice.error'
            );
        }

        if ($UTKInvoiceArray) {
            $this->_sendResponseData([
                'cancelResult' => 0
            ]);
        } else {
            $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
        }
    }

    /**
     * Отправить запрос на изменение стоимости комиссии(изменение скидки для услуги) для агента
     */
    public function actionSetDiscount()
    {
//        $salt = KPasswordHelper::makeSalt();
//        print $salt . PHP_EOL;
//        print CPasswordHelper::hashPassword('adminTaskPass', 8);
//
//        exit();

        $module = YII::app()->getModule('orderService');
        $orderWfMgr = $module->OrderWorkflowManager();

        $params = $this->_getRequestParams();

        $response = $orderWfMgr->setDiscount($params);

        if ($response) {
            $this->_sendResponse(true, $response, '');
        } else {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($orderWfMgr->getLastError()),
                $orderWfMgr->getLastError()
            );
        }
    }

    /**
     * Получение от УТК списка компаний и обновление их в КТ
     */
    public function actionGetClientsList()
    {
        set_time_limit(5400);
        $utkmodule = Yii::app()->getModule('orderService')->getModule('utk');

        $utkClient = $utkmodule->UtkClient($utkmodule);
        $utkValidator = $utkmodule->UtkRequestValidator($utkmodule);

        $params = $this->_getRequestParams();

        $response = $utkValidator->checkGetClientsListParams($params);

        if (!$response) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($utkValidator->getLastError()),
                $utkValidator->getLastError()
            );
        }

        $clientsList = $utkClient->makeRestRequest('getclientslist', $params, true);

        if (!$clientsList) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(UtkErrors::CANNOT_EXECUTE_GETCLIENTS_COMMAND),
                $utkClient->getLastError()
            );
        }

        $ordermodule = Yii::app()->getModule('orderService')->getModule('order');
        $agenciesMgr = $ordermodule->AgenciesMgr($ordermodule);

        $agenciesCount = 0;
        $agenciesUpdateCount = 0;
        $responseStatus = '';
        $responseLog = '';

        $changesQueueNumber = false;

        /** Обработчик данных от УТК */
        $listener = $utkmodule->GetClientsListListener(
            function ($info) use (&$responseStatus, &$responseLog) {
                $responseStatus = $info['status'];
                $responseLog = $info['log'];

                LogHelper::log(
                    PHP_EOL . __CLASS__ . '.' . __FUNCTION__ . PHP_EOL .
                    'Запрос к УТК GetClientsList:' . PHP_EOL .
                    'статус: ' . $responseStatus . ', лог: ' . $responseLog . PHP_EOL,
                    'trace', 'system.orderservice.utkrequests'
                );

                /** реконнект к базе данных - данных от УТК идут долго */
                Yii::app()->db->setActive(false);
                Yii::app()->db->setActive(true);
            },
            function ($result) use (&$changesQueueNumber) {
                if (!empty($result['numberMessage'])) {
                    $changesQueueNumber = $result['numberMessage'];
                }
            },
            function ($item) use (&$agenciesMgr, &$agenciesCount, &$agenciesUpdateCount) {
//                var_dump($item);
//                exit;


                $agenciesCount++;

                // обновим компанию
                if (isset($item['clientID_UTK']) && isset($item['client_GPTS_ID'])) {
                    $company = CompanyRepository::getByUTKOrGPTSId($item['clientID_UTK'], $item['client_GPTS_ID']);

                    if (is_null($company)) {
                        $company = new Company();
                    }
                } else {
                    LogHelper::logExt(
                        __CLASS__, __METHOD__,
                        'Не заданы ID УТК и GPTS ID для поиска компании', '',
                        [],
                        LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.utkrequests'
                    );
                    return;
                }

                if ($company->isManualEdited()) {
                    return;
                }

                if (!$company->setFromGetClientsListUTK($item)) {
                    LogHelper::logExt(
                        __CLASS__, __METHOD__,
                        'Не удалось сохранить компанию', '',
                        $company->getAttributes(),
                        LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.utkrequests'
                    );
                    return;
                }

                if ($company->save(false)) {
                    $agenciesUpdateCount++;
                } else {
                    LogHelper::logExt(
                        __CLASS__, __METHOD__,
                        'Не удалось сохранить компанию', '',
                        $company->getAttributes(),
                        LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.utkrequests'
                    );
                    return;
                }

                // обновим контракты компании
                if (isset($item['contracts']) && count($item['contracts'])) {
                    foreach ($item['contracts'] as $inputContractArr) {
                        if (isset($inputContractArr['contractIdUTK'])) {
                            $contract = ContractRepository::getByUTKId($inputContractArr['contractIdUTK']);
                        } else {
                            continue;
                        }

                        if (is_null($contract)) {
                            $contract = new Contract();
                        }

                        if ($contract->isManualEdited()) {
                            continue;
                        }

                        if (!$contract->setFromGetClientsListUTK($inputContractArr)) {
                            LogHelper::logExt(
                                __CLASS__, __METHOD__,
                                'Не удалось сохранить контракт', 'Ошибка по вх параметрах контракта',
                                $inputContractArr,
                                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.utkrequests'
                            );
                            continue;
                        }

                        $contract->bindCompany($company);

                        if (!$contract->save(false)) {
                            LogHelper::logExt(
                                __CLASS__, __METHOD__,
                                'Не удалось сохранить контракт', 'Ошибка БД',
                                $contract->getAttributes(),
                                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.utkrequests'
                            );
                        }
                    }
                }

                // обновим пользаков компании
                if (isset($item['clientUsers']) && count($item['clientUsers'])) {
                    foreach ($item['clientUsers'] as $clientUser) {
                        if (isset($clientUser['UTK_id']) && isset($clientUser['gpts_id'])) {
                            $account = AccountRepository::getByUTKorGPTSId($clientUser['UTK_id'], $clientUser['gpts_id']);
                        } else {
                            LogHelper::logExt(
                                __CLASS__, __METHOD__,
                                'Не удалось обновить аккаунт', 'Не заданы IDшники сущности',
                                $clientUser,
                                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.utkrequests'
                            );
                            continue;
                        }

                        if (is_null($account)) {
                            $account = new Account();
                            $account->setDateCreated(new DateTime());
                        }

                        if ($account->isManualEdited()) {
                            continue;
                        }

                        if (!$account->setFromGetClientsListUTK($clientUser)) {
                            LogHelper::logExt(
                                __CLASS__, __METHOD__,
                                'Не удалось обновить аккаунт', 'Ошибка по вх параметрах',
                                $clientUser,
                                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.utkrequests'
                            );
                            continue;
                        }

                        $account->bindCompany($company);

                        if (!$account->save(false)) {
                            LogHelper::logExt(
                                __CLASS__, __METHOD__,
                                'Не удалось сохранить аккаунт', 'Ошибка БД',
                                $clientUser,
                                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.utkrequests'
                            );
                        }
                    }
                }

//                var_dump($item);
//                exit;
//                $agenciesInfo = $agenciesMgr->updateAgencyInfo($item);


//                if ($agenciesInfo !== false) {
//                    $agenciesUpdateCount++;
//                }
            }
        );

        $parser = new JsonStreamingParser\Parser($clientsList, $listener);
        $parser->parse();

        if ($responseStatus == 1) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(UtkErrors::CANNOT_EXECUTE_GETCLIENTS_COMMAND) . ' ' . $responseLog,
                UtkErrors::CANNOT_EXECUTE_GETCLIENTS_COMMAND
            );
        }

        if ($changesQueueNumber !== false) {
            $response = $utkClient->makeRestRequest('getclientslist', [
                'operation' => 'messageReceived',
                'numberMessage' => $changesQueueNumber
            ]);

            if (!$response) {
                LogHelper::log(
                    PHP_EOL . __CLASS__ . '.' . __FUNCTION__ . PHP_EOL .
                    'Не удалось сообщить об успешном приеме изменений:' . PHP_EOL,
                    'trace', 'system.orderservice.utkrequests'
                );
            }
        }

        $this->_sendResponse(true, [
            'всего агентств получено' => $agenciesCount,
            'агентств обработано' => $agenciesUpdateCount
        ], '');

        /**
         * @deprecated
         */
        /*
        if ($response['status'] == 1) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(UtkErrors::CANNOT_EXECUTE_GETCLIENTS_COMMAND) . ' ' . $response['log'],
                UtkErrors::CANNOT_EXECUTE_GETCLIENTS_COMMAND
            );
        }

        if (isset($response['result']['numberMessage'])) {
            $changesQueueNumber = $response['result']['numberMessage'];
        }

        $module = Yii::app()->getModule('orderService')->getModule('order');
        $agenciesMgr = $module->AgenciesMgr($module);

        $agenciesInfo  = $agenciesMgr->updateAgenciesInfo($response['result']);

        if ($agenciesInfo === false) {
            $this->_sendResponse(false, array(),s
                $this->getErrorDescription($agenciesMgr->getLastError()),
                $utkClient->getLastError()
            );
        }

        if (!empty($changesQueueNumber)) {
            $params['operation'] = 'messageReceived';
            $params['numberMessage'] = $changesQueueNumber;
            $response = $utkClient->makeRestRequest('getclientslist', $params);
            if (!$response) {
                //todo сделать логирование неудачного запроса
            }
        }

        $this->_sendResponse(true, $agenciesInfo,'');

        */
    }
}
