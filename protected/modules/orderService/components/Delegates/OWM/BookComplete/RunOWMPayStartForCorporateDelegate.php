<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 01.06.17
 * Time: 15:10
 */
class RunOWMPayStartForCorporateDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (!$BookData = $this->getObjectFromContext('BookData')) {
            $this->addLog('Некорректный запуск делегата RunOWMPayStartForCorporateDelegate', 'error');
            return;
        }

        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        if ($BookData->hasResultToCompleteBooking() && $OrderModel->getCompany()->isCorporate()) {
            // запуск PayFinish асинхронно
            $AsyncTask = new AsyncTask();
            $AsyncTask->setModule(Yii::app()->getModule('orderService'));
            $AsyncTask->setTaskParams('orderService', 'OrderWorkflowManager',
                [
                    'action' => 'PayStart',
                    'orderId' => $OrderModel->getOrderId(),
                    'actionParams' => [
                        'services' => [
                            'serviceId' => $BookData->getServiceId(),
                            'invoicePrice' => 1
                        ]
                    ],
                    'usertoken' => $params['usertoken']
                ]
            );
            $this->setObjectToContext($AsyncTask);
        }
    }
}