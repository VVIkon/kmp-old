<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 07.04.17
 * Time: 15:50
 */
class ValidateRequiredOrderAddFieldsDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        // проверим обязательные поля заявки
        $orderAdditionalFields = OrderAdditionalFieldRepository::getOrderFieldWithId($OrderModel);

        foreach ($orderAdditionalFields as $orderAdditionalField) {
            if ($orderAdditionalField->isRequired() && $orderAdditionalField->isEmpty()) {
                $this->setError(OrdersErrors::REQUIRED_ADD_FIELD_IS_EMPTY);
                return;
            }
        }
    }
}