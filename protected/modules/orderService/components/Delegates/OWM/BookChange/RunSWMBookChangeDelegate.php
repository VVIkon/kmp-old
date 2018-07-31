<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/18/16
 * Time: 11:50 AM
 */
class RunSWMBookChangeDelegate extends AbstractDelegate
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

        if (!$SWM_FSM->can('SERVICEBOOKCHANGE')) {
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            $this->addLog("Не удалось запустить SERVICEBOOKCHANGE для услуги № {$params['serviceId']}", 'error');
            return null;
        }

        $SWM_BookChangeParams = [
            'serviceData' => [
                'orderService' => [
                    'dateStart' => $params['serviceData']['dateStart'],
                    'dateFinish' => $params['serviceData']['dateFinish'],
                ],
//                'tourists' => $params['serviceData']['tourists']
            ],
            'usertoken' => $params['usertoken'],
            'orderModel' => $params['object'],
            'userProfile' => $params['userProfile'],
            'orderId' => $OrderModel->getOrderId()
        ];

        $SWMResponse = $SWM_FSM->apply('SERVICEBOOKCHANGE', $SWM_BookChangeParams);

        if ($SWMResponse['status']) {
            $this->setError($SWMResponse['status']);
            return;
        }

        // если после запуска SWM BookCancel нужно перевести услугу в ручник
        if (isset($SWMResponse['response']['runOWMManual'])) {
            $this->addLog('Синхронно переводим услугу в ручник');

            $account = AccountRepository::getAccountById($params['userProfile']['userId']);

            $OWM_FSM = new StateMachine($OrderModel);
            $OWM_FSM->apply('ORDERMANUAL', [
                'serviceId' => $OrdersServices->getServiceID(),
                'comment' => 'Пользователь ' . (string)$account . ' запросил изменение брони',
                'userPermissions' => $params['userPermissions'],
                'userProfile' => $params['userProfile'],
                'usertoken' => $params['usertoken']
            ]);

            $OrdersServices->refresh();
            $OrderModel->refresh();

            $this->addResponse('orderStatus', $OrderModel->getStatus());
            $this->addResponse('serviceStatus', $OrdersServices->getStatus());
        } else {
            $this->mergeResponse($SWMResponse['response']);
        }
    }
}