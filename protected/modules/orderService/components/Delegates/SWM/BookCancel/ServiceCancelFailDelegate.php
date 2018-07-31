<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 27.03.17
 * Time: 16:16
 */
class ServiceCancelFailDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (isset($params['serviceCancelled']) && $params['serviceCancelled'] == false && !isset($params['newPenalties'])) {
            $OrderModel = new OrderModel();
            $OrderModel->unserialize($params['orderModel']);

            // восстановим объект сервиса
            $OrdersService = new OrdersServices();
            $OrdersService->unserialize($params['object']);

            // запуск OWM.Manual асинхронно
            $AsyncTask = new AsyncTask();
            $AsyncTask->setModule(Yii::app()->getModule('orderService'));
            $AsyncTask->setTaskParams('orderService', 'OrderWorkflowManager',
                [
                    'action' => 'Manual',
                    'orderId' => $OrderModel->getOrderId(),
                    'actionParams' => [
                        'serviceId' => $OrdersService->getServiceID(),
                        'comment' => 'Отмена выполняется вручную. Требуется вмешательство менеджера.'
                    ],
                    'usertoken' => $params['usertoken']
                ]
            );
            $this->setObjectToContext($AsyncTask);

            $this->setError(OrdersErrors::SERVICE_CANCELLATION_FAILED);
            $this->addLog("Отмена брони неуспешна, новых штрафов нет, отправляем услугу {$OrdersService->getServiceName()} в ручник");
        }
    }
}