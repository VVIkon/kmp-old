<?php

class ValidateServiceIsOnlineDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    public function run(array $params)
    {
        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        if ($OrdersServices->isOffline()) {
            $this->setError(OrdersErrors::SERVICE_IS_OFFLINE);
            return;
        }
    }
}