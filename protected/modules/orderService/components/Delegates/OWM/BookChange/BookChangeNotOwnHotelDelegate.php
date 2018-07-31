<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/23/16
 * Time: 10:36 AM
 */
class BookChangeNotOwnHotelDelegate extends AbstractDelegate
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

        $OrdersServices = OrdersServicesRepository::findById($params['serviceId']);

        if (is_null($OrdersServices)) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            $this->addLog("Не найдена услуга с ID {$params['serviceId']}", 'error');
            return null;
        }

        $RefSupplier = $OrdersServices->getSupplier();

        if (is_null($RefSupplier)) {
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            $this->addLog("Не найден поставщик в услуге № {$params['serviceId']}", 'error');
            return null;
        }

        if ($RefSupplier->hasAutoconfirmation()) {
            $oldStatus = $OrderModel->getStatus();

            $orderId = $OrderModel->getOrderId();

            $OrderHistory = new OrderHistory();
            $OrderHistory->setObjectData($OrderModel);
            $OrderHistory->setOrderData($OrderModel);
            $OrderHistory->setActionResult(0);

            if (empty($orderId)) {
                $this->setError(OrdersErrors::ORDER_NOT_FOUND);
                return null;
            }

            $orderForm = OrderForm::createInstance('system.orderservice');
            $orderSearchForm = OrderSearchForm::createInstance('system.orderservice');
            $order = $orderForm->getOrderByIdObj($orderId);

            $servicesInfo = $orderSearchForm->getOrdersServices($orderId);

            $allServicesHasSameStatus = function ($svcsInfo, $checkStatus) {
                foreach ($svcsInfo as $svcInfo) {
                    if ($svcInfo['status'] != $checkStatus) {
                        return false;
                    }
                }
                return true;
            };

            $allServicesInStatuses = function ($svcsInfo, $checkStatuses) {
                foreach ($svcsInfo as $svcInfo) {
                    if (!in_array($svcInfo['status'], $checkStatuses)) {
                        return false;
                    }
                }
                return true;
            };

            $serviceHasOneOfStatuses = function ($svcsInfo, $checkStatuses) {
                foreach ($svcsInfo as $svcInfo) {
                    if (in_array($svcInfo['status'], $checkStatuses)) {
                        return true;
                    }
                }
                return false;
            };

            if (count($servicesInfo) == 0) {
                $order->status = OrderForm::ORDER_STATUS_NEW;
                $OrderHistory->setCommentTpl('{{140}} {{123}}');
            }

            if ($allServicesInStatuses($servicesInfo, [
                    ServicesForm::SERVICE_STATUS_NEW,
                    ServicesForm::SERVICE_STATUS_W_BOOKED,
                    ServicesForm::SERVICE_STATUS_BOOKED,
                    ServicesForm::SERVICE_STATUS_DONE,
                ]) && $serviceHasOneOfStatuses($servicesInfo, [
                    ServicesForm::SERVICE_STATUS_W_BOOKED,
                    ServicesForm::SERVICE_STATUS_NEW
                ])
            ) {
                $order->status = OrderForm::ORDER_STATUS_NEW;
                $OrderHistory->setCommentTpl('{{140}} {{123}}');
            }

            if ($allServicesHasSameStatus($servicesInfo, ServicesForm::SERVICE_STATUS_BOOKED)) {
                $order->status = OrderForm::ORDER_STATUS_BOOKED;
                $OrderHistory->setCommentTpl('{{140}} {{130}}');
            }

            if ($allServicesInStatuses($servicesInfo,
                    [
                        ServicesForm::SERVICE_STATUS_NEW,
                        ServicesForm::SERVICE_STATUS_W_BOOKED,
                        ServicesForm::SERVICE_STATUS_BOOKED,
                        ServicesForm::SERVICE_STATUS_W_PAID,
                        ServicesForm::SERVICE_STATUS_P_PAID,
                        ServicesForm::SERVICE_STATUS_PAID,
                        ServicesForm::SERVICE_STATUS_DONE
                    ]
                ) && $serviceHasOneOfStatuses($servicesInfo, [
                    ServicesForm::SERVICE_STATUS_W_PAID,
                    ServicesForm::SERVICE_STATUS_P_PAID,
                    ServicesForm::SERVICE_STATUS_PAID
                ])
            ) {
                $order->status = OrderForm::ORDER_STATUS_W_PAID;
                $OrderHistory->setCommentTpl('{{140}} {{128}}');
            }

            if ($allServicesInStatuses($servicesInfo, [
                    ServicesForm::SERVICE_STATUS_PAID,
                    ServicesForm::SERVICE_STATUS_DONE
                ]) && $serviceHasOneOfStatuses($servicesInfo, [ServicesForm::SERVICE_STATUS_PAID])
            ) {
                $order->status = OrderForm::ORDER_STATUS_PAID;
                $OrderHistory->setCommentTpl('{{140}} {{125}}');
            }

            if ($allServicesInStatuses($servicesInfo, [
                    ServicesForm::SERVICE_STATUS_PAID,
                    ServicesForm::SERVICE_STATUS_DONE
                ]) && $serviceHasOneOfStatuses($servicesInfo, [ServicesForm::SERVICE_STATUS_DONE])
            ) {
                $order->status = OrderForm::ORDER_STATUS_DONE;
                $OrderHistory->setCommentTpl('{{140}} {{129}}');
            }

            if ($allServicesInStatuses($servicesInfo, [
                ServicesForm::SERVICE_STATUS_CANCELLED,
                ServicesForm::SERVICE_STATUS_VOIDED
            ])) {
                $order->status = OrderForm::ORDER_STATUS_ANNULED;
                $OrderHistory->setCommentTpl('{{140}} {{127}}');
            }

            if ($serviceHasOneOfStatuses($servicesInfo, [
                ServicesForm::SERVICE_STATUS_MANUAL
            ])) {
                $order->status = OrderForm::ORDER_STATUS_MANUAL;
                $OrderHistory->setCommentTpl('{{140}} {{124}}');
            }

            $order->updateOrder();

            if ($oldStatus != $order->status) {
                $OrderModel->setStatus($order->status);
                $this->params['object'] = $OrderModel->serialize();

                // сохраним результат аудита
                $this->addOrderAudit($OrderHistory);

                // запишем лог
                $this->addLog("Заявке № {$OrderModel->getOrderId()} присвоен агрегатный статус $order->status");
            }
        }
    }
}