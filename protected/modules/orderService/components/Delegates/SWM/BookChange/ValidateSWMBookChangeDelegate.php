<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/18/16
 * Time: 12:17 PM
 */
class ValidateSWMBookChangeDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        /**
         * DEPRECATED IN v3.4
         *
        if (!$OrdersServices->getOffer()->hasModifyAbility()) {
            $this->setError(OrdersErrors::SUPPLIER_DOESNT_SUPPORT_MODIFICATION);
            return null;
        }
         */

        if (!isset($params['serviceData']['orderService']['dateStart']) || !isset($params['serviceData']['orderService']['dateFinish'])) {
            $this->setError(OrdersErrors::START_OR_END_DATE_NOT_SET);
            return null;
        }

        $DateStart = new DateTime($params['serviceData']['orderService']['dateStart']);
        $DateStart->setTime(23, 59, 59);

        if ($DateStart === false || $DateStart->getTimestamp() < time()) {
            $this->setError(OrdersErrors::INCORRECT_START_DATE);
            return null;
        }

        $DatePlusYear = new DateTime();
        $DatePlusYear->modify('+1 year');

        if (strtotime($params['serviceData']['orderService']['dateFinish']) === false || strtotime($params['serviceData']['orderService']['dateFinish']) > $DatePlusYear->getTimestamp()) {
            $this->setError(OrdersErrors::INCORRECT_FINISH_DATE);
            return null;
        }
    }
}