<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/9/16
 * Time: 6:27 PM
 */
class ValidateSWMPayFinishDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (!isset($params['servicePaid'])) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            $this->addLog('Не указан параметр servicePaid', 'error');
        }
    }
}