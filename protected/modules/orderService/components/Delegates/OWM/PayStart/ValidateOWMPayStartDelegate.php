<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/16/16
 * Time: 4:00 PM
 */
class ValidateOWMPayStartDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        if ($OrderModel->inStatus(OrderModel::STATUS_MANUAL) && $params['userProfile']['userType'] != 1) {
            $this->setError(OrdersErrors::ORDER_STATUS_IS_BLOCKING_PAYSTART);
            return null;
        }

        if ($OrderModel->inStatus(OrderModel::STATUS_DONE) && $params['userProfile']['userType'] != 1) {
            $this->setError(OrdersErrors::ORDER_STATUS_IS_BLOCKING_PAYSTART);
            return null;
        }

        if (!$OrderModel->hasTourLeader()) {
            $this->setError(OrdersErrors::TOURLEADER_NOT_FOUND);
            return null;
        }

//        if($OrderModel->getCompany()->isAgent() || $OrderModel->getCompany()->isDirectSales()){
//            if (!$OrderModel->getOrderIDUTK()) {
//                $this->setError(OrdersErrors::INVOICE_NO_UTK_ID);
//                return null;
//            }
//        }
    }
}