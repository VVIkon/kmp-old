<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 28.03.17
 * Time: 18:31
 */
class OWMSetAdditionalDataDelegate extends AbstractDelegate
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


        foreach ($params['additionalFields'] as $additionalField) {

            if (isset($additionalField['orderId']) && empty($additionalField['serviceId']) && empty($additionalField['touristId'])) {
                // пришло поле заявки, запишем
                $orderAdditionalField = OrderAdditionalFieldRepository::getOrderFieldWithId($OrderModel, $additionalField['fieldTypeId']);
                if (is_null($orderAdditionalField)) {
                    $orderAdditionalField = new OrderAdditionalField();
                    $orderAdditionalField->bindOrder($OrderModel);
                    if (!$orderAdditionalField->trySetAddFieldTypeId($additionalField['fieldTypeId'])) {
                        $this->setError(OrdersErrors::INCORRECT_ADD_FIELD_TYPE_ID);
                        return;
                    }
                }

                if(!isset($additionalField['value'])){
                    $additionalField['value'] = null;
                }
                $orderAdditionalField->setValue($additionalField['value']);
                if (!$orderAdditionalField->isValid()) {
                    $this->setError(OrdersErrors::ADD_FIELD_NOT_VALID);
                    return;
                }

                $orderAdditionalField->save(false);

            } elseif (isset($additionalField['serviceId']) && empty($additionalField['orderId']) && empty($additionalField['touristId'])) {
                // запускаем зачем-то SWM (((
                $service = OrdersServicesRepository::findById($additionalField['serviceId']);

                if (is_null($service)) {
                    $this->setError(OrdersErrors::SERVICE_NOT_FOUND);
                    return;
                }

                $SWM_FSM = new StateMachine($service);
                $params['orderModel'] = $params['object'];
                $SWMResponse = $SWM_FSM->apply('SERVICESETADDITIONALDATA', [
                    'fieldTypeId' => $additionalField['fieldTypeId'],
                    'value' => $additionalField['value'],
                    'orderModel' => $params['object']
                ]);

                // критическую ошибку транслируем и завершаем действие
                if (!empty($SWMResponse['status'])) {
                    $this->setError(OrdersErrors::ADD_FIELD_NOT_VALID);
                    return null;
                }
            } elseif (isset($additionalField['touristId']) && empty($additionalField['orderId']) && empty($additionalField['serviceId'])) {
                // пришло поле туриста, запишем

                $orderAdditionalField = OrderAdditionalFieldRepository::getTouristFieldById($additionalField['touristId'], $additionalField['fieldTypeId']);
                if (is_null($orderAdditionalField)) {
                    $orderAdditionalField = new OrderAdditionalField();
                    $orderAdditionalField->bindTouristById($additionalField['touristId']);
                    if (!$orderAdditionalField->trySetAddFieldTypeId($additionalField['fieldTypeId'])) {
                        $this->setError(OrdersErrors::INCORRECT_ADD_FIELD_TYPE_ID);
                        return;
                    }
                }
                if (!isset($additionalField['value'])) {
                    $additionalField['value'] = null;
                }
                $orderAdditionalField->setValue($additionalField['value']);
                if (!$orderAdditionalField->isValid()) {
                    $this->setError(OrdersErrors::ADD_FIELD_NOT_VALID);
                    return;
                }
                $orderAdditionalField->save(false);
            } else {
                $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
                return;
            }
        }
    }
}