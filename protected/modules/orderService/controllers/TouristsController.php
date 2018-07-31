<?php

/**
 * Class TouristsController
 * Используется для реализации команд работы с туристами внешнего API
 */
class TouristsController extends SecuredRestController
{
    /**
     * Получить информацию о туристах в заказе
     */
    public function actionGetOrderTourists()
    {
        $module = YII::app()->getModule('orderService')->getModule('order');
        $ordersMgr = $module->OrdersMgr($module);
        $params = $this->_getRequestParams();

        $ordersValidator = $module->OrdersValidator($module);

        $response = $ordersValidator->checkGetOrderTourists($params);

        if (!$response) {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($ordersValidator->getLastError()),
                $ordersValidator->getLastError()
            );
        }

        $response = $ordersMgr->getOrderTourists($params);

        if ($response !== false) {
            $this->_sendResponse(true, $response, '');
        } else {

            $this->_sendResponse(false, array(),
                $this->getErrorDescription($ordersMgr->getLastError()),
                $ordersMgr->getLastError()
            );
        }
    }

    /**
     * Добавить туриста к заказу
     */
    public function actionSetTouristToOrder()
    {
        $module = YII::app()->getModule('orderService')->getModule('order');
        $touristsMgr = $module->TouristsManager($module);

        $params = $this->_getRequestParams();

        $response = $touristsMgr->addTourists($params);

        if ($response) {
            $this->_sendResponse(true, $response, '');
        } else {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($touristsMgr->getLastError()),
                $touristsMgr->getLastError()
            );
        }
    }

    /**
     * Добавить туриста к заказу
     */
    public function actionRemoveTouristFromOrder()
    {
        $module = YII::app()->getModule('orderService')->getModule('order');
        $orderWfMgr = $module->OrderWorkflowManager();

        $params = $this->_getRequestParams();

        $response = $orderWfMgr->removeTourist($params);

        if ($response) {
            $this->_sendResponse(true, $response, '');
        } else {
            $this->_sendResponse(false, array(),
                $this->getErrorDescription($orderWfMgr->getLastError()),
                $orderWfMgr->getLastError()
            );
        }

    }
}