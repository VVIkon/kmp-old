<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/23/16
 * Time: 10:35 AM
 */
class BookChangeOwnHotelDelegate extends AbstractDelegate
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

        if (!$RefSupplier->hasAutoconfirmation()) {
            $account = AccountRepository::getAccountById($params['userProfile']['userId']);

            $AsyncTask = new AsyncTask();
            $AsyncTask->setModule(Yii::app()->getModule('orderService'));
            $AsyncTask->setTaskParams('orderService', 'OrderWorkflowManager',
                [
                    'action' => 'Manual',
                    'orderId' => $OrderModel->getOrderId(),
                    'actionParams' => [
                        'serviceId' => $params['serviceId'],
                        'comment' => 'Пользователь ' . (string)$account . ' запросил подтверждение изменения данных бронирования'
                    ],
                    'usertoken' => $params['usertoken']
                ]
            );

            $this->setObjectToContext($AsyncTask);
        }
    }
}