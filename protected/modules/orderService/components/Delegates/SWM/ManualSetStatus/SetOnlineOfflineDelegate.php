<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/23/16
 * Time: 1:02 PM
 */
class SetOnlineOfflineDelegate extends AbstractDelegate
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

        if (isset($params['online'])) {
            $OrdersServices->setOffline(!$params['online']);
        }

        if(!$OrdersServices->save()){
            $this->setError(OrdersErrors::DB_ERROR);
            return;
        }
    }
}