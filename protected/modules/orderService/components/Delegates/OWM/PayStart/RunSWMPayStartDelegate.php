<?php

/**
 *
 */
class RunSWMPayStartDelegate extends AbstractDelegate
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

        if ($OrderModel->getCompany()->isAgent() || $OrderModel->getCompany()->isDirectSales()) {
            $Invoice = $this->getObjectFromContext(Invoice::class);

            $OrderServicesToRunSWM = [];

            // проверим, что все услуги в параметрах подходят
            foreach ($params['services'] as $service) {
                $OrderService = OrdersServicesRepository::findById($service['serviceId']);

                if (is_null($OrderService)) {
                    $this->addLog("Услуга с ID {$service['serviceId']} не найдена", 'error');
                    // удалим счет
                    $Invoice->delete();
                    $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
                    return;
                }

                $OrderServicesToRunSWM[$OrderService->getServiceID()] = $OrderService;
            }

            $invoiceId = $Invoice->getInvoiceId();

            foreach ($params['services'] as $service) {
                $SWMPayStartParams = [
                    'orderModel' => $params['object'],
                    'invoiceId' => $invoiceId,
                    'serviceId' => $service['serviceId'],
                    'amount' => $service['invoicePrice'],
                    'currency' => $Invoice->getCurrency()->getId(),
                    'orderId' => $OrderModel->getOrderId(),
                    'statusesOnly' => false
                ];

                $SWM_FSM = new StateMachine($OrderServicesToRunSWM[$service['serviceId']]);

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
            }
        }
    }
}