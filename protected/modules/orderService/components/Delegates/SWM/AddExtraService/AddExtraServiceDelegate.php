<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 14.07.17
 * Time: 18:04
 */
class AddExtraServiceDelegate extends AbstractDelegate
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

        // создание доп услуги
        $addService = $OrdersService->createAddService($params['addServiceOfferId'], $params['required']);
        $OrdersService->save();

        $OrdersServicesHistory = new OrdersServicesHistory();
        $OrdersServicesHistory->setObjectData($OrdersService);
        $OrdersServicesHistory->setOrderData($OrderModel);
        $OrdersServicesHistory->setCommentTpl('{{172}} {{addServiceName}}');
        $OrdersServicesHistory->setCommentParams([
            'addServiceName' => $addService->getName()
        ]);
        $OrdersServicesHistory->setActionResult(0);
        $this->addOrderAudit($OrdersServicesHistory);

        // log
        $this->addLog("Создана доп услуга {$addService->getName()}");
        $addService->setViewCurrency(CurrencyStorage::findByString($params['viewCurrency']));
        $this->addResponse('addService', $addService->toSOAddService());
    }
}