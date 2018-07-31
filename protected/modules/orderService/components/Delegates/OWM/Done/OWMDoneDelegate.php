<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/14/16
 * Time: 5:34 PM
 */
class OWMDoneDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        $OrderServices = $OrderModel->getOrderServices();

        if (count($OrderServices)) {
            foreach ($OrderServices as $OrderService) {
                if (!$OrderService->inStatus(OrdersServices::STATUS_DONE)) {
                    $this->setError(OrdersErrors::ALL_SERVICES_MUST_BE_DONE);
                    return null;
                }

//                if(!$OrderService->isPaid()){
//                    $this->setError(OrdersErrors::ALL_SERVICES_MUST_BE_PAID);
//                    return null;
//                }
            }
        }

        $OrderModel->setStatus(OrderModel::STATUS_DONE);
        $this->params['object'] = $OrderModel->serialize();

        if ($OrderModel->save()) {
            // запишем историю
            $OrderHistory = new OrderHistory();
            $OrderHistory->setObjectData($OrderModel);
            $OrderHistory->setOrderData($OrderModel);
            $OrderHistory->setActionResult(0);
            $OrderHistory->setCommentTpl('{{140}} {{129}}');
            $OrderHistory->setCommentParams([]);

            // запишем лог
            $this->addLog("Заявка № {$OrderModel->getOrderId()} получила статус Оформлено");

            // сохраним результат аудита
            $this->addOrderAudit($OrderHistory);

            $this->addResponse('orderService', OrderModel::STATUS_DONE);
        } else {
            $this->setError(OrdersErrors::DB_ERROR);
            return null;
        }
    }

}