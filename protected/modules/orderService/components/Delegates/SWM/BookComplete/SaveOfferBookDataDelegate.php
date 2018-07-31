<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/9/16
 * Time: 3:15 PM
 */
class SaveOfferBookDataDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $BookData = $this->getObjectFromContext('BookData');

        // запишем имеющиеся данные брони в оффер
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        $OrdersService->setBookData($BookData);

        if (!empty($BookData->getSupplierMessagesImploded())) {
            $OrdersServiceHistory = new OrdersServicesHistory();
            $OrdersServiceHistory->setOrderData($OrderModel);
            $OrdersServiceHistory->setObjectData($OrdersService);
            $OrdersServiceHistory->setActionResult(0);
            $OrdersServiceHistory->setCommentTpl('{{141}} {{message}}');
            $OrdersServiceHistory->setCommentParams([
                'message' => $BookData->getSupplierMessagesImploded()
            ]);

            $this->addOrderAudit($OrdersServiceHistory);
        }

        // запишем ID услуги, если пришла
        if ($BookData->getGateServiceId()) {
            $OrdersService->setServiceIDGP($BookData->getGateServiceId());
        }

        $OrdersService->save();
        $this->params['object'] = $OrdersService->serialize();
    }
}