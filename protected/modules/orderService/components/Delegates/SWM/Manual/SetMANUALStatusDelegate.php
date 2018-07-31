<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/12/16
 * Time: 5:29 PM
 */
class SetMANUALStatusDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        // установим ручник
        $OrdersServices->setStatus(OrdersServices::STATUS_MANUAL);
        $OrdersServices->save();

        // лог
        $this->addLog("Установлен ручной статус для услуги {$OrdersServices->getServiceID()}");

        // данные в респонс
        $this->addResponse('serviceStatus', $OrdersServices->getStatus());

        $OrdersServicesHistory = new OrdersServicesHistory();
        $OrdersServicesHistory->setObjectData($OrdersServices);
        $OrdersServicesHistory->setOrderData($OrderModel);
        $OrdersServicesHistory->setActionResult(0);

        // если установлен коммент, то добавим комментарий
        if(empty($params['comment'])){
            $OrdersServicesHistory->setCommentTpl('{{135}} {{124}}');
            $OrdersServicesHistory->setCommentParams([]);
        } else {
            $this->addNotificationData('comment', $params['comment']);

            $OrdersServicesHistory->setCommentTpl('{{135}} {{124}}. {{168}} {{comment}}.');
            $OrdersServicesHistory->setCommentParams([
                'comment' => $params['comment']
            ]);
        }

        $this->addOrderAudit($OrdersServicesHistory);
    }
}