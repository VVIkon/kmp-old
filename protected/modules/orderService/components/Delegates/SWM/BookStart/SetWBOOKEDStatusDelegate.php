<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/29/16
 * Time: 5:35 PM
 */
class SetWBOOKEDStatusDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);
        $OrdersService->makeWBooked();

        // сохраним комментарий при бронировании
        if (!empty($params['comment'])) {
            $OrdersService->setComment($params['comment']);
        }

        if (!$OrdersService->save(false)) {
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return null;
        }

        $this->params['object'] = $OrdersService->serialize();

        $OrdersServiceHistory = new OrdersServicesHistory();
        $OrdersServiceHistory->setOrderData($OrderModel);
        $OrdersServiceHistory->setObjectData($OrdersService);
        $OrdersServiceHistory->setActionResult(0);
        $OrdersServiceHistory->setCommentTpl('{{135}} {{131}}');

        $this->addOrderAudit($OrdersServiceHistory);
        $this->addResponse('serviceStatus', $OrdersService->getStatus());
    }
}