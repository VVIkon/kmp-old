<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 10/4/16
 * Time: 4:09 PM
 */
class ValidateBookCancelDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    public function run(array $params)
    {
        if (!isset($params['serviceId'])) {
            $this->setError(OrdersErrors::SERVICE_ID_NOT_SET);
            return null;
        }

        if(!isset($this->params['createPenaltyInvoice'])){
            $this->params['createPenaltyInvoice'] = true;
        }

        if ($this->params['createPenaltyInvoice'] == false && $params['userProfile']['userType'] != 1) {
            $this->setError(OrdersErrors::SERVICE_CANCEL_IMPOSSIBLE);
            return null;
        }

        $OrdersService = OrdersServices::model()->findByPk($params['serviceId']);

        if (is_null($OrdersService)) {
            $this->setError(OrdersErrors::SERVICE_NOT_FOUND);
            return null;
        }
    }
}