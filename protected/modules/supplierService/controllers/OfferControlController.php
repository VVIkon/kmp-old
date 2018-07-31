<?php

/**
 * Class OfferControlController
 * Реализует команды управления вспомогательными действиями, выполняемыми с оффером
 */
class OfferControlController extends SecuredRestController {

    /**
     * Команда получения правил тарифа
     */
    public function actionGetFareRule()
    {
        $this->runSupplierServiceAction('getFareRule');
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

        $response = $supplierMgr->runAction($action, $params);

        if ($response === false) {
            $this->_sendResponse(false, array(),
                $module->getError($supplierMgr->getLastError()),
                $supplierMgr->getLastError()
            );
        } else {
            $this->_sendResponse(true, $response, '');
        }
    }
}