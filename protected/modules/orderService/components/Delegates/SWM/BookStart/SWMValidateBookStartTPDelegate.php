<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 12.04.17
 * Time: 11:23
 */
class SWMValidateBookStartTPDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

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

        $serviceAddFields = OrderAdditionalFieldRepository::getServiceFieldWithId($OrdersService);
        $serviceReasonFailTPFields = array_filter($serviceAddFields, function (OrderAdditionalField $addField) {
            return $addField->AdditionalFieldType->isReasonFailTP();
        });

        // создание доп поля, если есть нарушения ТП и у услуги нет доп поля с оправданиями
        if ($OrdersService->hasTPViolations() && count($serviceReasonFailTPFields) == 0) {
            $reasonFailTPFields = $OrdersService->getOrderModel()->getCompany()->getAddFields();
            $reasonFailTPFields = array_filter($reasonFailTPFields, function (AdditionalFieldType $addField) {
                return $addField->isReasonFailTP();
            });

            if (count($reasonFailTPFields)) {
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

        // сама валидация
        if ($OrdersService->hasTPViolations() && !UserAccess::hasPermissions(62, $params['userPermissions'])) {
            $this->setError(OrdersErrors::TRAVEL_POLICY_FAIL_CODES);
            $this->addNotificationTemplate('tpviolation');
            return;
        }

        // уведомление об упущенной выгоде
        $orderAddField = OrderAdditionalFieldRepository::getServiceMinimalPriceField($OrdersService);

        if ($orderAddField) {
            $minimalPriceNotificationParams = [];
            $minimalPriceNotificationParams[strtolower($OrdersService->getServiceNameByType($OrdersService->getServiceType())) . 'MinimalPriceData'] = json_decode($orderAddField->getValue(), true);

            $orderTourists = $OrdersService->getOrderTourists();

            foreach ($orderTourists as $orderTourist) {
                $minimalPriceNotificationParams['tourists'][] = $orderTourist->getTourist()->getSSTouristStructure();
            }

            $this->addNotificationTemplate('minimalprice', $minimalPriceNotificationParams);
        }
    }
}