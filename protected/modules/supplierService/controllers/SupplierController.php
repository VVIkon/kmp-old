<?php

/**
 * Реализует команды получения информации от поставщиков по сервисной авторизации
 * User: v.ikonnikov
 * Date: 29.03.2017
 * Time: 12:54
 */
class SupplierController extends RestController
{
    /**
     * Получение данные о заявке/услугах от поставщика.
     * //массив данных услуг , для которых надо получить информацию о заявке из шлюза
    {
        "token": "fe27b68acc59770",
        "services" : [
            {
                "serviceID":  4,
                "serviceType": 1,
                "engineData" : {
                    "reservationId" : 1,
                    "offerId": 111,
                    "offerKey": 222,
                    "gateId": 5,
                    "data": {
                        "GPTS_order_ref": 12916,
                        "GPTS_service_ref": "5661062"
                    }
                }
            }
        ]
    }
     *
     * @return string
     */
    public function actionSupplierGetOrder()
    {
        $params = $this->_getRequestParams();
        $supplierMgr = new SupplierManager();
        $action = 'supplierGetOrder';
        try {
            $response = $supplierMgr->runAction($action, $params);
            $this->_sendResponse(true, $response, '');
        } catch (KmpException $ke) {
            $this->_sendResponseWithErrorCode($ke->getCode());
        }
    }


    /**
     *
     */
    public function actionSetServiceData()
    {
        $params = $this->_getRequestParams();
        $supplierMgr = new SupplierManager();
        $action = 'setServiceData';
        try {
            $response = $supplierMgr->runAction($action, $params);
            $this->_sendResponse(true, $response, '');
        } catch (KmpException $ke) {
//            LogHelper::logExt(
//                __CLASS__, __FUNCTION__, $action, $ke->getMessage(),
//                $params,
//                LogHelper::MESSAGE_TYPE_ERROR,
//                'system.supplierservice.error'
//            );
            $this->_sendResponseWithErrorCode($ke->getCode());
        }
    }
}