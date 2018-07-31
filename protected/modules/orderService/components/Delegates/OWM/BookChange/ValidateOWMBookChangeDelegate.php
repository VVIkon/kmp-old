<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/18/16
 * Time: 11:48 AM
 */
class ValidateOWMBookChangeDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (!isset($params['serviceId'])) {
            $this->setError(OrdersErrors::SERVICE_ID_NOT_SET);
            return null;
        }

        if (!isset($params['serviceData']) || !is_array($params['serviceData'])) {
            $this->setError(OrdersErrors::SERVICE_DATA_NOT_SET);
            return null;
        }

        if (!(isset($params['serviceData']['dateStart']) && isset($params['serviceData']['dateFinish']))) {
            $this->setError(OrdersErrors::SERVICE_DATA_NOT_SET);
            return null;
        }

        $dateStart = new DateTime($params['serviceData']['dateStart']);
        $dateFinish = new DateTime($params['serviceData']['dateFinish']);

        if($dateStart > $dateFinish){
            $this->setError(OrdersErrors::INCORRECT_FINISH_DATE);
            return null;
        }
    }
}