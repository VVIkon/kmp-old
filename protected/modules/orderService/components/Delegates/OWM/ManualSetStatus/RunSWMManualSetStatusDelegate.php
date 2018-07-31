<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/23/16
 * Time: 12:52 PM
 */
class RunSWMManualSetStatusDelegate extends AbstractDelegate
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
            $this->setError(OrdersErrors::SERVICE_NOT_FOUND);
            $this->addLog("Не найдена услуга с ID {$params['serviceId']}", 'error');
            return null;
        }

        // запишем коммент
        if (isset($params['comment']) && !is_null($params['comment'])) {
            $OrderHistory = new OrderHistory();
            $OrderHistory->setObjectData($OrderModel);
            $OrderHistory->setOrderData($OrderModel);
            $OrderHistory->setActionResult(0);
            $OrderHistory->setCommentTpl('{{152}} {{comment}}');
            $OrderHistory->setCommentParams([
                'comment' => $params['comment']
            ]);

            // сохраним результат аудита
            $this->addOrderAudit($OrderHistory);
        }

        // запишем статус или флаг онлайн, если требуется
        if (!empty($params['serviceStatus']) || $params['serviceStatus'] === 0 || !empty($params['online'])) {
            $SWM_FSM = new StateMachine($OrdersServices);

            if (!$SWM_FSM->can('SERVICEMANUALSETSTATUS')) {
                $this->setError(OrdersErrors::SERVICE_STATUS_IS_BLOCKING_ACTION);
                $this->addLog("Не удалось запустить SERVICEMANUALSETSTATUS для услуги № {$params['serviceId']}", 'error');
                return null;
            }

            $SWM_ManualSetStatusParams = [
                'serviceStatus' => $params['serviceStatus'],
                'online' => $params['online'],
                'usertoken' => $params['usertoken'],
                'orderModel' => $params['object'],
                'userProfile' => $params['userProfile'],
                'orderId' => $OrderModel->getOrderId()
            ];

            $SWMResponse = $SWM_FSM->apply('SERVICEMANUALSETSTATUS', $SWM_ManualSetStatusParams);

            // если возникла ошибка - транслируем ее
            if ($SWMResponse['status']) {
                $this->setError($SWMResponse['status']);
                return null;
            }

            // если все в порядке - запишем ответ в общий ответ
            $this->mergeResponse($SWMResponse['response']);
        } else {
            $this->addResponse('serviceStatus', $OrdersServices->getStatus());
        }
    }
}