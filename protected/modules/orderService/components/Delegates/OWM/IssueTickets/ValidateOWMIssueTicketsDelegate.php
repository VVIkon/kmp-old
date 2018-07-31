<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/10/16
 * Time: 12:20 PM
 */
class ValidateOWMIssueTicketsDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (!isset($params['serviceId'])) {
            $this->addLog('Неверно указан параметр serviceId', 'error');
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            return null;
        }
    }
}