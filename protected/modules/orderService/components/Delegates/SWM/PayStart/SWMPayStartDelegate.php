<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 27.01.17
 * Time: 12:19
 */
class SWMPayStartDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        if ($params['statusesOnly']) {
            $OrdersServices->payStartStatus();

            // добавим аудит
            $OrderModel = new OrderModel();
            $OrderModel->unserialize($params['orderModel']);

            $OrdersServicesHistory = new OrdersServicesHistory();
            $OrdersServicesHistory->setObjectData($OrdersServices);
            $OrdersServicesHistory->setOrderData($OrderModel);
            $OrdersServicesHistory->setCommentTpl('{{135}} {{128}}');
            $OrdersServicesHistory->setCommentParams([]);
            $OrdersServicesHistory->setActionResult(0);

            $this->addOrderAudit($OrdersServicesHistory);
        } else {
            $res = $OrdersServices->setInvoice($params['invoiceId'], $params['amount'], CurrencyStorage::findByString($params['currency']));
            if (is_null($res)) {
                $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
                return;
            }
            $OrdersServices->calculateRestPaymentAmount();
        }

        if (!$OrdersServices->save()) {
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return;
        }

        $this->addResponse('serviceStatus', $OrdersServices->getStatus());
    }
}