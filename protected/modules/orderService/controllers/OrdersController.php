<?php

use Symfony\Component\Validator\Validation;

/**
 * Class OrdersController
 * Используется для реализации команд работы с заявками внешнего API
 */
class OrdersController extends SecuredRestController
{
    protected function beforeAction($action)
    {
        $notSecuredActions = $this->getModule()->getConfig('noUserTokenActionsCheck');

        if (empty($params['action']) || !in_array($params['action'], $notSecuredActions)) {
            parent::beforeAction($action);
            return true;
        }

        return true;
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
     * Получить список заявок в JSON формате
     */
    public function actionGetOrderList()
    {
        $module = YII::app()->getModule('orderService')->getModule('order');
        $ordersMgr = $module->OrdersMgr($module);

        $params = $this->_getRequestParams();

        $filtersManager = new CommandFiltersManager();
        $params = $filtersManager->applyOrdersListFilters($params);

        if (!isset($params['detailsType'])) {
            $response = $ordersMgr->getOrdersLong($params);
        } elseif ($params['detailsType'] == 'long') {
            $response = $ordersMgr->getOrdersLong($params);
        } else {
            $response = $ordersMgr->getOrdersShort($params);
        }

        if ($response !== NULL) {
            $this->_sendResponse(true, $response, '');
        } else {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($ordersMgr->getLastError()),
                $ordersMgr->getLastError()
            );
        }

    }

    /**
     * Получение истории заявки
     */
    public function actionGetOrderHistory()
    {
        $params = $this->_getRequestParams();

        $lang = isset($params['lang']) ? strtolower($params['lang']) : null;
        $orderId = isset($params['orderId']) ? $params['orderId'] : null;

        $History = new History();
        $History->setLanguage($lang);
        $History->setOrderId($orderId);

        // валидация параметров
        $validator = Validation::createValidatorBuilder()
            ->addMethodMapping('loadValidatorMetadata')
            ->getValidator();

        $violations = $validator->validate($History);

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $this->_sendResponse(false, array(),
                    $this->getErrorDescription($violation->getMessage()),
                    $violation->getMessage()
                );
            }
        }

        $Histories = OrderHistoryRepository::getByOrderIdAndLang($orderId, $lang);

        if (!empty($Histories)) {
            $this->_sendResponse(true, $Histories, '');
        } else {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(OrdersErrors::ORDER_ID_INCORRECT),
                OrdersErrors::ORDER_ID_INCORRECT
            );
        }
    }

    /**
     * Получить информацию по указанной заявке
     */
    public function actionGetOrder()
    {
        $module = YII::app()->getModule('orderService')->getModule('order');
        $ordersMgr = $module->OrdersMgr($module);

        $params = $this->_getRequestParams();

        $response = $ordersMgr->getOrder($params);

        if ($response !== NULL) {
            $this->_sendResponse(true, $response, '');
        } else {
            $this->_sendResponse(false, array(), $this->getErrorDescription($ordersMgr->getLastError()), $ordersMgr->getLastError());
        }
    }

    /**
     * Получить информацию по cчетам и
     * оплатам указанной заявки
     */
    public function actionGetOrderInvoices()
    {
        $module = YII::app()->getModule('orderService')->getModule('order');
        $ordersMgr = $module->OrdersMgr($module);

        $params = $this->_getRequestParams();

        $response = $ordersMgr->getOrderInvoices($params);

        if ($response !== NULL) {
            $this->_sendResponse(true, $response, '');
        } else {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($ordersMgr->getLastError()),
                $ordersMgr->getLastError()
            );
        }
    }

    /**
     * Получить информацию по услугам и предложениям в заявке
     */
    public function actionGetOrderOffers()
    {
        $params = $this->_getRequestParams();

        if (!isset($params['orderId']) || $params['orderId'] < 0) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(OrdersErrors::ORDER_ID_NOT_SET),
                OrdersErrors::ORDER_ID_NOT_SET
            );
        }

        if (!isset($params['servicesIds']) || !is_array($params['servicesIds'])) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(OrdersErrors::SERVICE_ID_NOT_SET),
                OrdersErrors::SERVICE_ID_NOT_SET
            );
        }

        if (!isset($params['getInCurrency'])) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(OrdersErrors::CURRENCY_NOT_SET),
                OrdersErrors::CURRENCY_NOT_SET
            );
        }

        if (!isset($params['lang'])) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(OrdersErrors::LANGUAGE_NOT_SET),
                OrdersErrors::LANGUAGE_NOT_SET
            );
        }

        // проверим заявку
        $OnlyOrderModel = OrderModelRepository::getByOrderId($params['orderId']);
        if (is_null($OnlyOrderModel)) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(OrdersErrors::ORDER_NOT_FOUND),
                OrdersErrors::ORDER_NOT_FOUND
            );
        }
        // найдём заявку с услугой
        $OrderModel = OrderModelRepository::getByOrderWithServices($params['orderId'], $params['servicesIds']);
        if (is_null($OrderModel)) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(OrdersErrors::SERVICE_NOT_FOUND),
                OrdersErrors::SERVICE_NOT_FOUND
            );
        }


        // если число найденных услуг в заявке не совпало с числом запрашиваемых,
        // то указаны неверные услуги
        if (count($params['servicesIds']) != $OrderModel->countServices()) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(OrdersErrors::SERVICES_NOT_MATCH),
                OrdersErrors::SERVICES_NOT_MATCH
            );
        }

        $CurrencyRates = CurrencyRates::getInstance();
        $currencyId = $CurrencyRates->getIdByCode($params['getInCurrency']);

        $Currency = CurrencyStorage::getById($currencyId);

        // если нашлось
        if ($OrderModel instanceof OrderModel) {
            $OrderModel->setLang($params['lang']);
            $OrderModel->setCurrency($Currency);

            // валидация параметров
            $validator = Validation::createValidatorBuilder()
                ->addMethodMapping('loadValidatorMetadata')
                ->getValidator();

            $violations = $validator->validate($OrderModel);

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->_sendResponse(false, array(),
                        $this->getErrorDescription($violation->getMessage()),
                        $violation->getMessage()
                    );
                }
            }

            // получаем оффер
            $response = $OrderModel->getOffers();

//            list($queryCount, $queryTime) = Yii::app()->db->getStats();
//            echo "Query count: $queryCount, Total query time: " . sprintf('%0.5f', $queryTime) . "s";
//            echo PHP_EOL;
//            exit;

            if (is_array($response)) {
                // выдаем ответ
                $this->_sendResponse(true, $response, '');
            } else {
                $this->_sendResponse(false, array(),
                    $this->getErrorDescription($response),
                    $response
                );
            }
        } else {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(OrdersErrors::ORDER_ID_INCORRECT),
                OrdersErrors::ORDER_ID_INCORRECT
            );
        }
    }

    /**
     * Физическое удаление заявки из БД
     */
    public function actionRemoveOrder()
    {
        $module = YII::app()->getModule('orderService')->getModule('order');
        $ordersMgr = $module->OrdersMgr($module);

        $params = $this->_getRequestParams();

        $response = $ordersMgr->removeOrder($params);

        if ($response !== NULL) {
            $this->_sendResponse(true, $response, '');
        } else {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($ordersMgr->getLastError()),
                $ordersMgr->getLastError()
            );
        }
    }

    /**
     * Вызов команды Order Workflow Manager
     * для выполнения операции над заявкой
     */
    public function actionOrderWorkflowManager()
    {
        $module = YII::app()->getModule('orderService');
        $orderWfMgr = $module->OrderWorkflowManager();

        $params = $this->_getRequestParams();

        $response = $orderWfMgr->runAction($params);

        if ($response !== false) {
            $this->_sendResponse(true, $response, '');
        } else {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($orderWfMgr->getLastError()),
                $orderWfMgr->getLastError()
            );
        }

    }

    /**
     * Получение приложенных документов к заявке
     */
    public function actionGetOrderDocuments()
    {
        $module = YII::app()->getModule('orderService')->getModule('order');

        $docMgr = $module->DocumentsMgr($module);

        $params = $this->_getRequestParams();

        $response = $docMgr->getOrderDocuments($params);

        if ($response !== false) {
            $this->_sendResponse(true, $response, '');
        } else {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($docMgr->getLastError()),
                $docMgr->getLastError()
            );
        }
    }

    /**
     * Добавить документ в заявку
     */
    public function actionAddDocumentToOrder()
    {
        $this->_sendErrorResponseIfNoPermissions([20, 45, 49]);

        $module = YII::app()->getModule('orderService');

        $docMgr = $module->DocumentsMgr($module);

        $params = $this->_getRequestParams();

        $response = $docMgr->addDocument($params);

        if ($response !== false) {
            $this->_sendResponse(true, $response, '');
        } else {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($docMgr->getLastError()),
                $docMgr->getLastError()
            );
        }
    }

    /**
     * Получение всех менеджеров,
     * которые относятся к заявке
     *
     * Используется в чате
     */
    public function actionGetOrderManagers()
    {
        $params = $this->_getRequestParams();

        if (empty($params['orderId'])) {
            $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_ID_INCORRECT);
        }

        $order = OrderModelRepository::getByOrderId($params['orderId']);

        if (is_null($order)) {
            $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_NOT_FOUND);
        }

        $userProfile = Yii::app()->user->getState('userProfile');

        if ($userProfile['userId'] != $order->getCreator()->getUserId() && !UserAccess::hasPermissions([40, 41, 42])) {
            $this->_sendResponseWithErrorCode(self::NOT_ENOUGH_RIGHTS_FOR_OPERATION);
        }

        $responseArr = [
            'clientManager' => [                          // Ответственный менеджер заявки от клиента
                'id' => $order->getCompanyManager()->getUserId()
            ],
            'managerKMP' => [                             // Ответственный менеджер заявки от KMP
                'id' => $order->getKMPManager()->getUserId(),
            ],
            'creator' => [                                // пользователь создатель заявки
                'id' => $order->getCreator()->getUserId(),
            ],
        ];

        $this->_sendResponseData($responseArr);
    }

     /* Сохранение правил тарифов
     * @param
     * {
            "offerKey": "ZleOYEn5aesehNAO",
            "viewCurrency" : "EUR",
            "usertoken": "{{userToken}}",
            "token": "fe27b68acc59770",
            "offerId": 101,
            "tripId" : 51
        }
     * @returm
     */
    public function actionSetFareRules()
    {
        $params = $this->_getRequestParams();
        if (!isset($params['offerKey']) ) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(OrdersErrors::CANNOT_GET_OFFER),
                OrdersErrors::CANNOT_GET_OFFER
            );
        }
        if (!isset($params['offerId']) || !is_numeric($params['offerId']) ) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(OrdersErrors::OFFER_ID_NOT_SET),
                OrdersErrors::OFFER_ID_NOT_SET
            );
        }
        if (!isset($params['tripId']) || !is_numeric($params['tripId']) ) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription(OrdersErrors::TRIP_ID_NOT_SET),
                OrdersErrors::TRIP_ID_NOT_SET
            );
        }


        $module = YII::app()->getModule('orderService');
        $ruleMgr = $module->FareRuleMgr($module);

        // делаем запрос GetFareRule
        $apiClient = new ApiClient(Yii::app()->getModule('orderService'));
        $ruleResponse = $apiClient->makeRestRequest('supplierService', 'GetFareRule', $params);

        if (!isset($ruleResponse)) {
            $this->_sendResponse(false, array(), $this->getErrorDescription(OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR), OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR );
            return null;
        }
        $ruleData = json_decode($ruleResponse, true);
        if ($ruleData['status'] == 1) {
            $this->_sendResponse(false, array(), $this->getErrorDescription(OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR), OrdersErrors::SUPPLIER_SERVICE_FATAL_ERROR );
            return null;
        }
        if (isset($ruleData) && $ruleData['status'] == 0) {
            $bodyRules = StdLib::nvl($ruleData['body']['rules']);

            $response = $ruleMgr->addFareRule($bodyRules, $params);

            if ($response == true) {
                $this->_sendResponse(true, ['saved' => true], '');
            } else {
                $err=$ruleMgr->getLastError();
                $this->_sendResponse(false, array(), $this->getErrorDescription($err), $err );
            }
        }
    }

    public function actionServiceCheckStatus()
    {
        $params = $this->_getRequestParams();

        $EventManager = EventManager::getInstance();
        $EventManager->init(Yii::app()->getModule('orderService')->getConfig('gearman'));
        $Event = EventRepository::getEventByCommand('OrderSync');
        if (is_null($Event)) {
            $this->_sendResponseWithErrorCode(OrdersErrors::INCORRECT_OWM_ACTION);
        }

        $CurrGate = 5;
        $services=[];

        $orderServices = OrdersServicesRepository::findOrderServiceForCheckStatus();
        foreach ($orderServices as $orderService) {
            $Order = $orderService->getOrderModel();

            // проверка наличия структуры шлюза
            $engineData = $orderService->getOffer()->getEngineData();
            if (!is_array($engineData) ||  count($engineData) == 0 || $engineData['gateId'] != $CurrGate) {
                continue;
            }
            $params['orderId'] = $Order->getOrderId();
            $params['serviceId'] = $orderService->getServiceID();
            $orders[] = $Order->getOrderId();
            $services[] = $orderService->getServiceID();

            // инициализируем машину состояний и применяем действие
            $OWM_FSM = new StateMachine($Order);
            try {
                $response = $OWM_FSM->apply($Event->getEvent(), $params);
            } catch (FSMException $e) {
                LogHelper::logExt(
                    __CLASS__, __METHOD__,
                    'Запуск StateMachine', "Ошибка машины состояний {$e->getMessage()}", '',
                    LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.error'
                );
                $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
                return;
            }

            LogHelper::logExt(get_class($this), __METHOD__, "По услуге получеy ответ", '', ['response'=>$response], 'trace', 'system.orderservice.info');

        }
//        LogHelper::logExt(get_class($this), __METHOD__, 'Для проверки статуса услуг отобраны заявки', '', ['orders'=>$orders], 'trace', 'system.orderservice.info');
        LogHelper::logExt(get_class($this), __METHOD__, 'Для проверки статуса услуг отобраны заявки', '', ['services'=>$services], 'trace', 'system.orderservice.info');
    }

    /**
     * Отправка на указанный eMail документа заявки
     */
    public function actionSendDocumentToUser()
    {
        $params = $this->_getRequestParams();
        if (empty($params['email'])) {
            $this->_sendResponseWithErrorCode(OrdersErrors::EMAIL_NOT_FOUND);
        }
        if (empty($params['documentId'])) {
            $this->_sendResponseWithErrorCode(OrdersErrors::DOCUMENT_ID_NOT_FOUND);
        }
        $this->_sendErrorResponseIfNoPermissions([7]);

        $module = Yii::app()->getModule('orderService');
        try {
            $sendDocumentMgr = new SendDocumentMgr($module);
            $sendDocumentMgr->setSubject('Информация из системы kmp.travel');
            $response = $sendDocumentMgr->run($params);
            if ($response){
                LogHelper::logExt(get_class($this), __METHOD__, "Отправка документа пользователю", '', ['params'=>$params,'response'=>$response], 'trace', 'system.orderservice.info');
                $this->_sendResponseData(['sended' => true]);
            }else{
                $this->_sendResponseWithErrorCode(OrdersErrors::DOCUMENT_FOR_SENDING_NOT_FOUND);
            }
        } catch (InvalidArgumentException $e) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Отправка документа пользователю', $e->getMessage(),
                $params,
                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.error'
            );

            $this->_sendResponseWithErrorCode($e->getCode());
        }

    }
}