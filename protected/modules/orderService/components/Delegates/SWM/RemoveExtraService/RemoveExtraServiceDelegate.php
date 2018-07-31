<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 18.07.17
 * Time: 18:18
 */
class RemoveExtraServiceDelegate extends AbstractDelegate
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

        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        // удаление доп услуги
        $OrdersService->removeAddService($params['addServiceId']);
        $OrdersService->save();

        $OrdersServicesHistory = new OrdersServicesHistory();
        $OrdersServicesHistory->setObjectData($OrdersService);
        $OrdersServicesHistory->setOrderData($OrderModel);
        $OrdersServicesHistory->setCommentTpl('{{174}} {{addServiceName}}');
        $OrdersServicesHistory->setCommentParams([
            'addServiceName' => $this->params['addServiceToDeleteName']
        ]);
        $OrdersServicesHistory->setActionResult(0);
        $this->addOrderAudit($OrdersServicesHistory);

        // log
        $this->addLog("Доп услуга {$this->params['addServiceToDeleteName']} удалена");

        $this->addResponse('removed', true);
    }
}