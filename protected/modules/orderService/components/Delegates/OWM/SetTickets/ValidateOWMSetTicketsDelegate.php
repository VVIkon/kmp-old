<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 12/14/16
 * Time: 12:23 PM
 */
class ValidateOWMSetTicketsDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if(!isset($params['serviceId']) || !isset($params['ticketData'])){
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            return;
        }
    }
}