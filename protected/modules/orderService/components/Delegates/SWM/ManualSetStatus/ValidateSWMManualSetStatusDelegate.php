<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/23/16
 * Time: 4:20 PM
 */
class ValidateSWMManualSetStatusDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (!(isset($params['online']) || isset($params['serviceStatus']))) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            return;
        }
    }
}