<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/23/16
 * Time: 4:23 PM
 */
class ServiceStatusSetDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        if (isset($params['serviceStatus'])) {
            if (!$OrdersServices->setStatus($params['serviceStatus'])) {
                $this->setError(OrdersErrors::INCORRECT_SERVICE_STATUS);
                return;
            }

            if (!$OrdersServices->save()) {
                $this->setError(OrdersErrors::DB_ERROR);
                return;
            }

            $this->params['object'] = $OrdersServices->serialize();

            $OrdersServicesHistory = new OrdersServicesHistory();
            $OrdersServicesHistory->setObjectData($OrdersServices);
            $OrdersServicesHistory->setOrderData($OrderModel);
            $OrdersServicesHistory->setCommentTpl("{{154}} {{{$OrdersServices->getStatusMsgCode()}}}");
            $OrdersServicesHistory->setCommentParams([]);
            $OrdersServicesHistory->setActionResult(0);

            $this->addLog("Установлен статус {$OrdersServices->getStatus()} для услуги {$OrdersServices->getServiceID()}");

            $this->addOrderAudit($OrdersServicesHistory);
        }

        $this->addResponse('serviceStatus', $OrdersServices->getStatus());
    }
}