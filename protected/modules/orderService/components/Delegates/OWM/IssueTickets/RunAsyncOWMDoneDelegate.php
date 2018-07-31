<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/16/16
 * Time: 1:03 PM
 */
class RunAsyncOWMDoneDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if ($this->hasResponse('issueTicketsSuccess')) {
            $OrderModel = new OrderModel();
            $OrderModel->unserialize($params['object']);

            $AsyncTask = new AsyncTask();
            $AsyncTask->setModule(Yii::app()->getModule('orderService'));
            $AsyncTask->setTaskParams('orderService', 'OrderWorkflowManager',
                [
                    'action' => 'Done',
                    'orderId' => $OrderModel->getOrderId(),
                    'actionParams' => [
                        'serviceId' => $params['serviceId']
                    ],
                    'usertoken' => $params['usertoken']
                ]
            );
            $this->setObjectToContext($AsyncTask);
        }
    }
}