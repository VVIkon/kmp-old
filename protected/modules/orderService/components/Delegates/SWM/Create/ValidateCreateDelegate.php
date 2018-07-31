<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 8/8/16
 * Time: 11:02 AM
 */
class ValidateCreateDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    public function run(array $params)
    {
        $OrdersServices = new OrdersServices();
        $OrdersServices->setValidator(new ServicesValidator(Yii::app()->getModule('orderService')));
        $err_code = $OrdersServices->validate($params);

        if($err_code){
            $this->setError($err_code);
        }
    }
}