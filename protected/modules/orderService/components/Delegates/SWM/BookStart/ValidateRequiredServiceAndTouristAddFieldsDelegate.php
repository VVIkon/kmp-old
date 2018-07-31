<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 07.04.17
 * Time: 16:07
 */
class ValidateRequiredServiceAndTouristAddFieldsDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        // проверим обязательные поля услуги
        $serviceAdditionalFields = OrderAdditionalFieldRepository::getServiceFieldWithId($OrdersService);

        foreach ($serviceAdditionalFields as $serviceAdditionalField) {
            if ($serviceAdditionalField->isRequired() && $serviceAdditionalField->isEmpty()) {
                $this->setError(OrdersErrors::REQUIRED_ADD_FIELD_IS_EMPTY);
                return;
            }
        }

        // проверим обязательные поля туристов в услуге
        $orderTourists = $OrdersService->getOrderTourists();

        foreach ($orderTourists as $orderTourist) {
            $touristAdditionalFields = OrderAdditionalFieldRepository::getTouristFieldWithId($orderTourist);

            foreach ($touristAdditionalFields as $touristAdditionalField) {
                if ($touristAdditionalField->isRequired() && $touristAdditionalField->isEmpty()) {
                    $this->setError(OrdersErrors::REQUIRED_ADD_FIELD_IS_EMPTY);
                    return;
                }
            }
        }
    }
}