<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/9/16
 * Time: 5:55 PM
 */
class ValidateOWMPayFinishDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (!isset($params['services']) || !is_array($params['services'])) {
            $this->addLog('Неверно указан параметр services', 'error');
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            return null;
        }

        foreach ($params['services'] as $service) {
            if (!isset($service['serviceId']) || !isset($service['servicePaid'])) {
                $this->addLog('Неверно указан параметр serviceId или servicePaid', 'error');
                $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
                return null;
            }
        }
    }
}