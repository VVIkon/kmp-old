<?php

/**
 * Class OfferInfoController
 * Реализует команды получения информации от поставщиков
 */
class OfferInfoController extends SecuredRestController
{
    /**
     * Получение информации по предложению от поставщика
     */
    public function actionGetOffer()
    {
        $this->runSupplierServiceAction('getOffer');
    }

    /**
     * Получение информации по штрафам
     */
    public function actionGetCancelRules()
    {
        $this->runSupplierServiceAction('getCancelRules');
    }

    /**
     * Отмена брони
     */
    public function actionSupplierServiceCancel()
    {
        $this->runSupplierServiceAction('supplierServiceCancel');
    }

    /**
     * Модификация брони
     */
    public function actionSupplierModifyService()
    {
        $this->runSupplierServiceAction('supplierModifyService');
    }

    /**
     * Команда запуска бронирования сервиса
     */
    public function actionServiceBooking()
    {
        $this->log(json_encode($this->_getRequestParams()));
        $this->runSupplierServiceAction('serviceBooking');
    }

    /**
     * Команда запуска бронирования сервиса
     */
    public function actionSupplierGetServiceStatus()
    {
        $this->runSupplierServiceAction('getServiceStatus');
    }

    /**
     * Команда запуска выписки билета
     */
    public function actionIssueTickets()
    {
        $response = $this->runSupplierServiceAction('issueTickets');
        LogHelper::logExt(
            __CLASS__, __FUNCTION__, 'supplier.issueTickets', 'issue tickets response',
            $response,
            LogHelper::MESSAGE_TYPE_INFO,
            'system.supplierservice.*'
        );
        $this->_sendResponse(true, $response, '');
    }

    public function actionGetETickets()
    {
        $response = $this->runSupplierServiceAction('getEtickets');
        $this->_sendResponse(true, $response, '');
    }

    /**
     * Метод запуска команд сервиса поставщиков
     * @param string $action Запускаемая команда
     */
    private function runSupplierServiceAction($action)
    {
        $module = YII::app()->getModule('supplierService');
        $params = $this->_getRequestParams();
        $supplierMgr = new SupplierManager();
        $response = null;
        try {
            $response = $supplierMgr->runAction($action, $params);
        } catch (KmpException $ke) {
            LogHelper::logExt(
                __CLASS__, __FUNCTION__, $action, $ke->getMessage(),
                $params,
                LogHelper::MESSAGE_TYPE_ERROR,
                'system.supplierservice.error'
            );
            $this->_sendResponseWithErrorCode($ke->getCode(), $ke->__get('params'));
        }

// LogHelper::logExt(get_class($this), __METHOD__, '----------Offer-2', '', ['$response'=>$response], 'info', 'system.searcherservice.info');

        if ($response === false) {
            $this->_sendResponse(false, array(),
                $module->getError($supplierMgr->getLastError()),
                $supplierMgr->getLastError()
            );
        } else {
            if (StdLib::nvl($response['status'],0) == 1){
                $this->_sendResponse(false, $response, ErrorHelper::getErrorDescription($this->getModule(), $response['errorCode']),$response['errorCode']);
            }elseif(StdLib::nvl($response['bookErrorCode'],0) > 0 ) {
                $this->_sendResponse(false, $response, ErrorHelper::getErrorDescription($this->getModule(), $response['bookErrorCode']),$response['bookErrorCode'], $response['supplierMessages']);
            }else {
                $this->_sendResponse(true, $response, '');
            }
        }
    }
}