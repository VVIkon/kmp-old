<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/9/16
 * Time: 5:53 PM
 */
class RunSWMPayFinishDelegate extends AbstractDelegate
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

        $serviceIds = [];

        foreach ($params['services'] as $service) {
            $OrdersServices = OrdersServicesRepository::findById($service['serviceId']);

            if (is_null($OrdersServices)) {
                $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
                $this->addLog("Не найдена услуга с ID {$service['serviceId']}", 'error');
                return null;
            }

            $SWM_FSM = new StateMachine($OrdersServices);

            if ($SWM_FSM->can('SERVICEPAYFINISH')) {
                $SWMPayFinishParams = [
                    'orderModel' => $params['object'],
                    'userProfile' => $params['userProfile'],
                    'servicePaid' => $service['servicePaid'],
                    'usertoken' => $params['usertoken'],
                    'orderId' => $OrderModel->getOrderId()
                ];

                $SWMResponse = $SWM_FSM->apply('SERVICEPAYFINISH', $SWMPayFinishParams);

                if (isset($SWMResponse['response']['serviceStatus'])) {
                    $serviceIds[$service['serviceId']] = $SWMResponse['response']['serviceStatus'];
                }
            } else {
                $this->addLog("Не удалось запустить SERVICEPAYFINISH для услуги № {$service['serviceId']}", 'error');
            }
        }

        $this->addResponse('orderStatus', $OrderModel->getStatus());
        $this->addResponse('servicesStatuses', $serviceIds);
    }
}