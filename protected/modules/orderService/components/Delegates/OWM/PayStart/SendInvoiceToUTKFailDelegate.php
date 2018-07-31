<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 27.01.17
 * Time: 12:19
 */
class SendInvoiceToUTKFailDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        // если ошибка отправки в УТК
        if(isset($params['UTKSendSuccess']) && $params['UTKSendSuccess'] == false){
            $OrderModel = new OrderModel();
            $OrderModel->unserialize($params['object']);

            $serviceStatuses = [];

            // запустить Ручник
            foreach ($params['services'] as $service) {
                $OrdersServices = OrdersServicesRepository::findById($service['serviceId']);

                if (is_null($OrdersServices)) {
                    $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
                    $this->addLog("Не найдена услуга с ID {$service['serviceId']}", 'error');
                    return null;
                }

                $SWM_FSM = new StateMachine($OrdersServices);

                // если услуга уже в ручнике, не вызываем SWM Manual
                if (!$SWM_FSM->can('SERVICEMANUAL')) {
                    continue;
                }

                $SWM_ManualParams = [
                    'usertoken' => $params['usertoken'],
                    'orderModel' => $params['object'],
                    'userProfile' => $params['userProfile'],
                    'orderId' => $OrderModel->getOrderId(),
                ];

                $SWMResponse = $SWM_FSM->apply('SERVICEMANUAL', $SWM_ManualParams);

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

            // удалить объект счета
            $Invoice = $this->getObjectFromContext(Invoice::class);
            $Invoice->delete();

            $this->setError(OrdersErrors::CANNOT_CREATE_INVOICE);
        }
    }
}