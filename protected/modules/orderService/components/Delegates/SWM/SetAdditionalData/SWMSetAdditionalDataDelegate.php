<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 30.03.17
 * Time: 15:03
 */
class SWMSetAdditionalDataDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrdersService = new OrdersServices();
        $OrdersService->unserialize($params['object']);

        $orderAdditionalField = OrderAdditionalFieldRepository::getServiceFieldWithId($OrdersService, $params['fieldTypeId']);

        if (is_null($orderAdditionalField)) {
            $orderAdditionalField = new OrderAdditionalField();
            $orderAdditionalField->bindService($OrdersService);
            if (!$orderAdditionalField->trySetAddFieldTypeId($params['fieldTypeId'])) {
                $this->setError(OrdersErrors::INCORRECT_ADD_FIELD_TYPE_ID);
                return;
            }
        }

        $orderAdditionalField->setValue($params['value']);

        if(!$orderAdditionalField->isValid()){
            $this->setError(OrdersErrors::ADD_FIELD_NOT_VALID);
            return;
        }

        $orderAdditionalField->save(false);
    }
}