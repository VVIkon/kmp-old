<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/14/16
 * Time: 1:37 PM
 */
class SWMDoneDelegate extends AbstractDelegate
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

        $OrdersServices->setStatus(OrdersServices::STATUS_DONE);

        if ($OrdersServices->save(false)) {
            // создадим Аудит для услуги
            $OrdersServicesHistory = new OrdersServicesHistory();
            $OrdersServicesHistory->setOrderData($OrderModel);
            $OrdersServicesHistory->setObjectData($OrdersServices);
            $OrdersServicesHistory->setActionResult(0);
            $OrdersServicesHistory->setCommentTpl("{{135}} {{129}}");
            $OrdersServicesHistory->setCommentParams([]);

            $this->addOrderAudit($OrdersServicesHistory);

            $this->addLog("Услуга № {$OrdersServices->getServiceID()} получила статус Оформлено");

            $this->addResponse('serviceStatus', OrdersServices::STATUS_DONE);
        } else {
            $this->setError(OrdersErrors::DB_ERROR);
            $this->addLog("Не удалось сохранить услугу № {$OrdersServices->getServiceID()}");
        }
    }
}