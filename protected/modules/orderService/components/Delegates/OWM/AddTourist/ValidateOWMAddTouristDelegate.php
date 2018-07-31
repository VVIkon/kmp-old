<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/29/16
 * Time: 11:53 AM
 */
class ValidateOWMAddTouristDelegate extends AbstractDelegate
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

        // Заявка не оффлайновая
        if ($OrderModel->isOffline()) {
            $this->setError(OrdersErrors::CANNOT_ADD_TOURIST_TO_OFFLINE_ORDER);
            return;
        }

        // данная логика не определена, игнорируем параметр
//        if (isset($params['isTourLeader']) && $params['isTourLeader']) {
//            $OrderTourleader = OrderTouristRepository::getTourleaderByOrderId($OrderModel->getOrderId());
//
//            if ($OrderTourleader) {
//                $this->setError(OrdersErrors::TOURLEADER_ALREADY_EXISTS);
//                return;
//            }
//        }
    }
}