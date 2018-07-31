<?php

/**
 * Class UtkHandlerController
 * Используется для реализации обработчика запросов со стороны UTK
 */
class UtkHandlerController extends RestController
{
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
     * Создать или обновить
     * заявку по информации из УТК
     */
    public function actionOrder()
    {
        $params = $this->_getRequestParams();

        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'Получение заявки из УТК', '',
            $params,
            LogHelper::MESSAGE_TYPE_INFO, 'system.orderservice.utkrequests'
        );

        $token = $params['token'];
        unset($params['token']);

        // инициализация Eventmanager
        $EventManager = EventManager::getInstance();
        $EventManager->init(Yii::app()->getModule('orderService')->getConfig('gearman'));

        $Event = EventRepository::getEventByCommand('Import');
        if (is_null($Event)) {
            $this->_sendResponseWithErrorCode(OrdersErrors::INCORRECT_OWM_ACTION);
        }

        // инициализируем машину состояний и применяем действие
        $Order = new OrderModel();
        $OWM_FSM = new StateMachine($Order);        

        try {
            $response = $OWM_FSM->apply($Event->getEvent(), [
                'orderData' => $params,
                'orderId' => $Order->getOrderId()
            ]);
        } catch (FSMException $e) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Запуск StateMachine', "Ошибка машины состояний {$e->getMessage()}", '',
                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.error'
            );
            $this->_sendResponseWithErrorCode(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return;
        }

        if ((int)$response['status'] !== 0) {
            $this->_sendResponseWithErrorCode($response['status']);
        } else {
            $this->_sendResponseData($response['response']);
        }
    }

    /**
     * Создать или обновить данные счёта
     */
    public function actionInvoice()
    {
        $params = $this->_getRequestParams();

        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'Получение счета из УТК', '',
            $params,
            LogHelper::MESSAGE_TYPE_INFO, 'system.orderservice.utkrequests'
        );

        $module = YII::app()->getModule('orderService')->getModule('utk');
        $ordersModule = YII::app()->getModule('orderService')->getModule('order');

        $utkManager = $module->UtkManager($module, $ordersModule);

        $response = $utkManager->setInvoiceInfo($params);

        if ($response) {
            $this->_sendResponse(true, $response, '');
        } else {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Получение счета из УТК вызвало ошибку', $this->getErrorDescription($utkManager->getLastError()),
                $params,
                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.utkrequests'
            );

            $this->_sendResponse(false, array(),
                $this->getErrorDescription($utkManager->getLastError()),
                $utkManager->getLastError()
            );
        }
    }

    /**
     * Создать или обновить данные оплаты
     */
    public function actionPayment()
    {
        $module = YII::app()->getModule('orderService')->getModule('utk');
        $ordersModule = YII::app()->getModule('orderService')->getModule('order');

        $utkManager = $module->UtkManager($module, $ordersModule);

        $params = $this->_getRequestParams();

        $response = $utkManager->payFinishBySync($params);

        if ($response) {
            $this->_sendResponse(true, $response, '');
        } else {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Получение заявки из УТК', $this->getErrorDescription($orderWfMgr->getLastError()),
                $params,
                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.utkrequests'
            );

            $this->_sendResponse(false, array(),
                $this->getErrorDescription($utkManager->getLastError()),
                $utkManager->getLastError()
            );
        }
    }
}
