<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/10/16
 * Time: 12:15 PM
 */
class RunSWMIssueTicketsDelegate extends AbstractDelegate
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

        if (!$SWM_FSM->can('SERVICEISSUETICKETS')) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            $this->addLog("Не удалось запустить SERVICEISSUETICKETS для услуги № {$params['serviceId']}", 'error');
            return null;
        }

        $this->addResponse('orderStatus', $OrderModel->getStatus());

        $params['orderModel'] = $params['object'];
        $params['orderId'] = $OrderModel->getOrderId();

        $SWMResponse = $SWM_FSM->apply('SERVICEISSUETICKETS', $params);

        // если возникла ошибка - транслируем ее
        if ($SWMResponse['status']) {
            $this->setError($SWMResponse['status']);
            return null;
        }

        // если все в порядке - запишем ответ в общий ответ
        $this->mergeResponse($SWMResponse['response']);
    }
}