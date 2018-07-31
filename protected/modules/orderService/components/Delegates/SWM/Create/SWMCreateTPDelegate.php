<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 15.05.17
 * Time: 13:14
 */
class SWMCreateTPDelegate extends AbstractDelegate
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
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        // выполнение TP
        $travelPolicy = new TravelPolicy($OrderModel->getCompany(), $OrdersService->getServiceType());

        try {
            $travelPolicy->applyExecute($OrdersService);
        } catch (TravelPolicyException $e) {
            $this->addLog($e->getMessage(), 'error');
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return;
        }

        // создание доп поле, если есть нарушения ТП
        if ($OrdersService->hasTPViolations()) {
            $reasonFailTPFields = $OrdersService->getOrderModel()->getCompany()->getAddFields();
            $reasonFailTPFields = array_filter($reasonFailTPFields, function (AdditionalFieldType $addField) {
                return $addField->isReasonFailTP();
            });

            if(count($reasonFailTPFields)){
                $reasonFailTPField = array_shift(array_values($reasonFailTPFields));
            }

            $this->addNotificationTemplate('tpviolation');

            if (isset($reasonFailTPField)) {
                $orderAddField = new OrderAdditionalField();
                $orderAddField->bindService($OrdersService);
                $orderAddField->bindAdditionalFieldType($reasonFailTPField);
                $orderAddField->save(false);
            }
        }
    }
}