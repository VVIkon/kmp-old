<?php

class ValidateAuthorizationDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    public function run(array $params)
    {
        $service = new OrdersServices();
        $service->unserialize($params['object']);

        // действия авторизации работают только для корпораторов
        if (!$service->getOrderModel()->getCompany()->isCorporate()) {
            return;
        }

        $serviceHasAuthorizationInProgress = AuthServiceRepository::countNotCompletedServiceAuth() > 0;

        if ($serviceHasAuthorizationInProgress) {
            $this->setError(OrdersErrors::ACTION_BLOCKED_BY_AUTH);
            return;
        }
    }
}