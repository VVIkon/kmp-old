<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/18/16
 * Time: 12:26 PM
 */
class HotelSWMBookChangeFailureDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

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

        // заворачиваем весь код в отелях
        if ($OrdersServices->getServiceType() == 1 && isset($params['modifyResult'])) {
            if (in_array($params['modifyResult'], [1, 2])) {
                $OrdersServiceHistory = new OrdersServicesHistory();
                $OrdersServiceHistory->setObjectData($OrdersServices);
                $OrdersServiceHistory->setOrderData($OrderModel);

                switch ($params['modifyResult']) {
                    case 1: // 1=не изменена,
                        $this->setError(OrdersErrors::BOOK_CHANGE_FAILED);
                        $OrdersServices->setStatus(OrdersServices::STATUS_MANUAL);
                        $OrdersServices->save();
                        $this->params['object'] = $OrdersServices->serialize();
                        $OrdersServiceHistory->setCommentTpl('{{146}}');

                        break;
                    case 2: // 2=невозможно изменение
                        $this->setError(OrdersErrors::BOOK_CHANGE_IMPOSSIBLE);
                        $OrdersServiceHistory->setCommentTpl('{{147}}');
                        break;
                }

                $OrdersServiceHistory->setActionResult(1);

                // Если бронь собственного отеля который не имеет возможности изменять бронь автоматически
                $flag = $OrdersServices->getOffer()->getSupplier()->hasAutoconfirmation();
                if ($flag) {
                    $OrdersServiceHistory->setCommentParams(['comment' => 'Клиент запросил отмену брони']);
                }
                $this->addOrderAudit($OrdersServiceHistory);
            }
        } else {
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            $this->addLog('Некорректный запуск делегата или не определен параметр modifyResult', 'error');
            return null;
        }
    }
}