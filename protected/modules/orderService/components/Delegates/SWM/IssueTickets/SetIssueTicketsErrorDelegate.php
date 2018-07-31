<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/10/16
 * Time: 1:28 PM
 */
class SetIssueTicketsErrorDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_POST_ACTION;

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

        $OrdersServicesHistory = new OrdersServicesHistory();
        $OrdersServicesHistory->setObjectData($OrdersServices);
        $OrdersServicesHistory->setOrderData($OrderModel);
        $OrdersServicesHistory->setCommentParams([]);


        if (isset($params['status']) && $params['status']) {
            $OrdersServicesHistory->setCommentTpl("{{144}}");
            $OrdersServicesHistory->setActionResult(1);

            $this->addLog("Билеты на услугу № {$OrdersServices->getServiceID()} не могут быть выписаны", 'info');
            $this->addResponse('issueTicketsFailure', true);
            $this->addResponse('status', $params['status']);
        } else {
            $OrdersServicesHistory->setCommentTpl("{{145}}");
            $OrdersServicesHistory->setActionResult(0);

            $this->addLog("Выписаны билеты на услугу № {$OrdersServices->getServiceID()}", 'info');
            $this->addResponse('issueTicketsSuccess', true);
        }

        $this->addOrderAudit($OrdersServicesHistory);

        $this->addResponse('serviceStatus', $OrdersServices->getStatus());
    }
}