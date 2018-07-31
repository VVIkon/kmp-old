<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/17/16
 * Time: 4:55 PM
 */
class ModifyResponse
{
    protected $refNumber;
    protected $orderId;
    protected $status;

    public function __construct(array $params)
    {
        $this->refNumber = isset($params['refNumber'])? $params['refNumber'] : 0;
        $this->orderId = isset($params['orderId'])? $params['orderId'] : 0;
        $this->status = isset($params['status'])? $params['status'] : 0;
    }

    public function hasErrors()
    {
        return is_null($this->status);
    }
}