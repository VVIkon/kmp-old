<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 20.09.17
 * Time: 12:51
 */
class RunAsyncOWMManualDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if ($this->hasResponse('issueTicketsFailure')) {
            $OrderModel = new OrderModel();
            $OrderModel->unserialize($params['object']);

            $AsyncTask = new AsyncTask();
            $AsyncTask->setModule(Yii::app()->getModule('orderService'));
            if (isset($params['response']['status'])) {
                $this->setError($params['response']['status']);
            }
            $manualParams = [
                'action' => 'Manual',
                'orderId' => $OrderModel->getOrderId(),
                'actionParams' => [
                    'serviceId' => $params['serviceId'],
                    'comment' => StdLib::nvl($params['response']['comment']),
                ],
                'usertoken' => $params['usertoken']
            ];
            $AsyncTask->setTaskParams('orderService', 'OrderWorkflowManager', $manualParams);
            $this->setObjectToContext($AsyncTask);
        }
    }
}