<?php

/**
 * Ставит агрегатный статус заявки
 * User: rock
 * Date: 8/5/16
 * Time: 2:34 PM
 */
class AggregateStatusDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_POST_ACTION;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);
        $oldStatus = $OrderModel->getStatus();

        $orderId = $OrderModel->getOrderId();

        if (empty($orderId)) {
            return null;
        }

        $OrderHistory = new OrderHistory();
        $OrderHistory->setObjectData($OrderModel);
        $OrderHistory->setOrderData($OrderModel);
        $OrderHistory->setActionResult(0);

        $orderSearchForm = OrderSearchForm::createInstance('system.orderservice');
        $servicesInfo = $orderSearchForm->getOrdersServices([$OrderModel->getOrderId()]);

        $allServicesHasSameStatus = function ($svcsInfo, array $checkStatus) {
            foreach ($svcsInfo as $svcInfo) {
                if ($svcInfo['status'] != $checkStatus) {
                    return false;
                }
            }
            return true;
        };

        $allServicesInStatuses = function ($svcsInfo, array $checkStatuses) {
            foreach ($svcsInfo as $svcInfo) {
                if (!in_array($svcInfo['status'], $checkStatuses)) {
                    return false;
                }
            }
            return true;
        };

        $serviceHasOneOfStatuses = function ($svcsInfo, array $checkStatuses) {
            foreach ($svcsInfo as $svcInfo) {
                if (in_array($svcInfo['status'], $checkStatuses)) {
                    return true;
                }
            }
            return false;
        };

        if (count($servicesInfo) == 0) {
            $OrderModel->setStatus(OrderModel::STATUS_NEW);
            $OrderHistory->setCommentTpl('{{140}} {{123}}');
        }

        if ($allServicesInStatuses($servicesInfo, [
                ServicesForm::SERVICE_STATUS_NEW,
                ServicesForm::SERVICE_STATUS_W_BOOKED,
                ServicesForm::SERVICE_STATUS_BOOKED,
                ServicesForm::SERVICE_STATUS_DONE,
                ServicesForm::SERVICE_STATUS_CANCELLED,
                ServicesForm::SERVICE_STATUS_VOIDED
            ]) && $serviceHasOneOfStatuses($servicesInfo, [
                ServicesForm::SERVICE_STATUS_W_BOOKED,
                ServicesForm::SERVICE_STATUS_NEW
            ])
        ) {
            $OrderModel->setStatus(OrderModel::STATUS_NEW);
            $OrderHistory->setCommentTpl('{{140}} {{123}}');
        }

        if ($allServicesInStatuses($servicesInfo, [
                ServicesForm::SERVICE_STATUS_BOOKED,
                ServicesForm::SERVICE_STATUS_DONE,
                ServicesForm::SERVICE_STATUS_CANCELLED
            ])
        ) {
            $OrderModel->setStatus(OrderModel::STATUS_BOOKED);
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
            $OrderModel->setStatus(OrderModel::STATUS_W_PAID);
            $OrderHistory->setCommentTpl('{{140}} {{128}}');
        }

        if ($allServicesInStatuses($servicesInfo, [
                ServicesForm::SERVICE_STATUS_PAID,
                ServicesForm::SERVICE_STATUS_DONE
            ]) && $serviceHasOneOfStatuses($servicesInfo, [ServicesForm::SERVICE_STATUS_PAID])
        ) {
            $OrderModel->setStatus(OrderModel::STATUS_PAID);
            $OrderHistory->setCommentTpl('{{140}} {{125}}');
        }

        if ($allServicesInStatuses($servicesInfo, [
                ServicesForm::SERVICE_STATUS_PAID,
                ServicesForm::SERVICE_STATUS_DONE
            ]) && $serviceHasOneOfStatuses($servicesInfo, [ServicesForm::SERVICE_STATUS_DONE])
        ) {
            $OrderModel->setStatus(OrderModel::STATUS_DONE);
            $OrderHistory->setCommentTpl('{{140}} {{129}}');
        }

        if ($allServicesInStatuses($servicesInfo, [
            ServicesForm::SERVICE_STATUS_CANCELLED,
            ServicesForm::SERVICE_STATUS_VOIDED
        ])) {
            $OrderModel->setStatus(OrderModel::STATUS_ANNULED);
            $OrderHistory->setCommentTpl('{{140}} {{127}}');
        }

        if ($serviceHasOneOfStatuses($servicesInfo, [
            ServicesForm::SERVICE_STATUS_MANUAL
        ])) {
            $OrderModel->setStatus(OrderModel::STATUS_MANUAL);
            $OrderHistory->setCommentTpl('{{140}} {{124}}');
        }

        $OrderModel->save();

        if ($oldStatus != $OrderModel->getStatus()) {
            $OrderModel->setStatus($OrderModel->getStatus());
            $this->params['object'] = $OrderModel->serialize();

            // сохраним результат аудита
            $this->addOrderAudit($OrderHistory);

            // запишем лог
            $this->addLog("Заявке № {$OrderModel->getOrderId()} присвоен агрегатный статус {$OrderModel->getStatus()}");
        }
    }
}