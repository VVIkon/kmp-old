<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/23/16
 * Time: 1:03 PM
 */
class OrderSetStatusDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        if (!$OrderModel->setStatus($params['orderStatus'])) {
            $this->setError(OrdersErrors::INCORRECT_ORDER_STATUS);
            return;
        }

        if (!$OrderModel->save()) {
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return;
        }

        $OrderHistory = new OrderHistory();
        $OrderHistory->setObjectData($OrderModel);
        $OrderHistory->setOrderData($OrderModel);
        $OrderHistory->setActionResult(0);
        $OrderHistory->setCommentTpl("{{153}} {{{$OrderModel->getStatusMsgCode()}}}");
        $OrderHistory->setCommentParams([]);

        $this->addLog("Установлен статус заявки {$params['orderStatus']}");

        // сохраним результат аудита
        $this->addOrderAudit($OrderHistory);

        $this->addResponse('orderStatus', $OrderModel->getStatus());
    }
}