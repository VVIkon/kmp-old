<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/23/16
 * Time: 5:02 PM
 */
class RunSWMManualDelegate extends AbstractDelegate
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

        $SWM_FSM = new StateMachine($OrdersServices);

        if (!$SWM_FSM->can('SERVICEMANUAL')) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            $this->addLog("Не удалось запустить SERVICEMANUAL для услуги № {$params['serviceId']}", 'error');
            return null;
        }

        $comment = (!empty($params['comment']) ? $params['comment'] : null);

        $this->addNotificationTemplate('manager', [
            'comment' => $comment
        ]);

        $SWM_ManualParams = [
            'comment' => $comment,
            'usertoken' => $params['usertoken'],
            'orderModel' => $params['object'],
            'orderId' => $OrderModel->getOrderId(),
            'userProfile' => $params['userProfile']
        ];

        $SWMResponse = $SWM_FSM->apply('SERVICEMANUAL', $SWM_ManualParams);

        // если возникла ошибка - транслируем ее
        if ($SWMResponse['status']) {
            $this->setError($SWMResponse['status']);
            return null;
        }

        // если все в порядке - запишем ответ в общий ответ
        $this->mergeResponse($SWMResponse['response']);
    }
}