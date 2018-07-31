<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 9/9/16
 * Time: 4:05 PM
 */
class NewSalesTermsNewStatusDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $BookData = $this->getObjectFromContext('BookData');

        if ($BookData->getNewOfferData()) {
            $OrdersServices = new OrdersServices();
            $OrdersServices->unserialize($params['object']);
            $OrdersServices->setStatus(OrdersServices::STATUS_NEW);
            $OrdersServices->save(false);

            $this->params['object'] = $OrdersServices->serialize();

            $this->addResponse('serviceStatus', OrdersServices::STATUS_NEW);
        }
    }
}