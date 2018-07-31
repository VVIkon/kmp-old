<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/29/16
 * Time: 5:32 PM
 */
class SetAgreementSetDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        $OrdersService->agreementSet();
        $this->params['object'] = $OrdersService->serialize();

        if(!$OrdersService->save()){
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return null;
        }
    }
}