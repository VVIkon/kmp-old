<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 27.01.17
 * Time: 12:18
 */
class SendInvoiceToUTKSuccessDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        // если успешно отправлено в УТК
        if (isset($params['UTKSendSuccess']) && $params['UTKSendSuccess']) {
            $OrderModel = new OrderModel();
            $OrderModel->unserialize($params['object']);

            $serviceStatuses = [];

            if ($OrderModel->getCompany()->isAgent() || $OrderModel->getCompany()->isDirectSales()) {
                $Invoice = $this->getObjectFromContext(Invoice::class);

                // зафиксировать услуги в статусе ожидания
                foreach ($params['services'] as $service) {
                    $SWMPayStartParams = [
                        'orderModel' => $params['object'],
                        'invoiceId' => $Invoice->getInvoiceId(),
                        'serviceId' => $service['serviceId'],
                        'amount' => $service['invoicePrice'],
                        'currency' => $Invoice->getCurrency()->getId(),
                        'orderId' => $OrderModel->getOrderId(),
                        'statusesOnly' => true
                    ];

                    $OrderService = OrdersServicesRepository::findById($service['serviceId']);
                    $SWM_FSM = new StateMachine($OrderService);

                    if (!$SWM_FSM->can('SERVICEPAYSTART')) {
                        $this->setError(OrdersErrors::SERVICE_STATUS_IS_BLOCKING_PAYSTART);
                        $this->addLog("Не удалось запустить PAYSTART для услуги № {$service['serviceId']}", 'error');
                        return null;
                    }

                    $SWMResponse = $SWM_FSM->apply('SERVICEPAYSTART', $SWMPayStartParams);

                    // если возникла ошибка - транслируем ее
                    if ($SWMResponse['status']) {
                        $this->setError($SWMResponse['status']);
                        return null;
                    }

                    $serviceStatuses[] = [
                        'serviceId' => $service['serviceId'],
                        'serviceStatus' => $SWMResponse['response']['serviceStatus']
                    ];
                }

                $this->addResponse('serviceStatuses', $serviceStatuses);
            } else {
                // запуск PayFinish асинхронно
                $AsyncTask = new AsyncTask();
                $AsyncTask->setModule(Yii::app()->getModule('orderService'));
                $AsyncTask->setTaskParams('orderService', 'OrderWorkflowManager',
                    [
                        'action' => 'PayFinish',
                        'orderId' => $OrderModel->getOrderId(),
                        'actionParams' => [
                            'services' => [
                                [
                                    'serviceId' => $params['services']['serviceId'],
                                    'servicePaid' => InvoiceForm::STATUS_PAYED_COMPLETELY
                                ]
                            ]
                        ],
                        'usertoken' => $params['usertoken']
                    ]
                );
                $this->setObjectToContext($AsyncTask);
            }
        }
    }
}