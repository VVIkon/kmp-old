<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/9/16
 * Time: 6:30 PM
 */
class SWMPayFinishDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['orderModel']);

        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        $statusMsgCode = 0;
        $status = '';

        switch ($params['servicePaid']) {
            case InvoiceForm::STATUS_PAYED_COMPLETELY:
                $OrdersServices->setStatus(OrdersServices::STATUS_PAID);
                $status = 'PAID';
                $statusMsgCode = 125;

                // запуск IssueTickets асинхронно
                $AsyncTask = new AsyncTask();
                $AsyncTask->setModule(Yii::app()->getModule('orderService'));
                $AsyncTask->setTaskParams('orderService', 'OrderWorkflowManager',
                    [
                        'action' => 'IssueTickets',
                        'orderId' => $OrderModel->getOrderId(),
                        'actionParams' => [
                            'serviceId' => $OrdersServices->getServiceID()
                        ],
                        'usertoken' => $params['usertoken']
                    ]
                );
                $this->setObjectToContext($AsyncTask);
                break;
            case InvoiceForm::STATUS_PAYED_PARTIAL:
                $OrdersServices->setStatus(OrdersServices::STATUS_P_PAID);
                $status = 'P_PAID';
                $statusMsgCode = 132;
                break;
            default:
                break;
        }

        if ($statusMsgCode) {
            if(!$OrdersServices->save()){
                $this->setError(OrdersErrors::DB_ERROR);
                return null;
            }

            $this->params['object'] = $OrdersServices->serialize();

            $OrdersServicesHistory = new OrdersServicesHistory();
            $OrdersServicesHistory->setObjectData($OrdersServices);
            $OrdersServicesHistory->setOrderData($OrderModel);
            $OrdersServicesHistory->setCommentTpl("{{135}} {{{$statusMsgCode}}}");
            $OrdersServicesHistory->setCommentParams([]);
            $OrdersServicesHistory->setActionResult(0);

            $this->addOrderAudit($OrdersServicesHistory);
            $this->addLog("Услуга получила статус {$status}", 'info');
        }

        $this->addResponse('serviceStatus', $OrdersServices->getStatus());
    }
}