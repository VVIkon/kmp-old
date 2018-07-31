<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/24/16
 * Time: 11:19 AM
 */
class RunOWMAddTouristDelegate extends AbstractDelegate
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

        // если есть задача по работе с туристами
        if (isset($params['serviceData']['touristData']) && count($params['serviceData']['touristData'])) {
            $OWMAddTourist = [
                'usertoken' => $params['usertoken'],
                'userProfile' => $params['userProfile'],
                'userPermissions' => $params['userPermissions'],
                'serviceId' => $params['serviceId'],
                'touristData' => $params['serviceData']['touristData'],
                'orderId' => $OrderModel->getOrderId()
            ];

            $OWM_FSM = new StateMachine($OrderModel);

            if (!$OWM_FSM->can('ORDERTOURISTTOSERVICE')) {
                $this->setError(OrdersErrors::ORDER_STATUS_INCORRECT_FOR_ACTION);
                return;
            }

            $response = $OWM_FSM->apply('ORDERTOURISTTOSERVICE', $OWMAddTourist);

            if ($response['status']) {
                $this->setError($response['status']);
                return null;
            }

            if (isset($response['response']['result']) && count($response['response']['result'])) {
                foreach ($response['response']['result'] as $resultItem) {
                    if (!$resultItem['success']) {
                        $this->setError($resultItem['errorCode']);
                        return;
                    }
                }

                $this->mergeResponse($response['response']);
            } else {
                $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
                return null;
            }
        }
    }
}