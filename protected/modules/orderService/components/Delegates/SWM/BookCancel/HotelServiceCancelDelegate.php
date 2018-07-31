<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/4/16
 * Time: 5:35 PM
 */
class HotelServiceCancelDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        // только для проживания
        if ($OrdersService->getServiceType() == 1 && isset($params['serviceCancelled']) && $params['serviceCancelled']) {
            // поставим статус
            $OrdersService->cancel();
            $this->params['object'] = $OrdersService->serialize();
            $OrdersService->save();

            // запишем аудит
            $OrdersServicesHistory = new OrdersServicesHistory();
            $OrdersServicesHistory->setOrderData($OrderModel);
            $OrdersServicesHistory->setCommentTpl('{{135}} {{134}}');
            $OrdersServicesHistory->setObjectData($OrdersService);
            $OrdersServicesHistory->setActionResult(0);
            $OrdersServicesHistory->setCommentParams([]);

            $this->addLog("Услуга № {$OrdersService->getServiceID()} получила статус Аннулирована");

            $this->addOrderAudit($OrdersServicesHistory);
        }
    }
}