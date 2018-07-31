<?php

/**
 * Проверяет, чтобы услуга была онлайн
 */
class SWMValidateServiceIsOnlineDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        if($OrdersService->isOffline()){
            $this->setError(OrdersErrors::ACTION_IMPOSSIBLE_FOR_OFFLINE_ORDER);
            return;
        }
    }
}