<?php

/**
 * Делегат добавления туриста к заявке
 * весь старый код зашит сюда
 */
class OWMAddTouristDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        // если указан ID туриста, то обновляем инфу о нем
        try {
            $OrderTourist = $OrderModel->setTourist($params);
            $this->params['touristId'] = $OrderTourist->getTouristID();

            if (isset($params['userAdditionalFields']) && count($params['userAdditionalFields'])) {
                foreach ($params['userAdditionalFields'] as $userAdditionalField) {
                    if (empty($userAdditionalField['fieldTypeId'])) {
                        $this->setError(OrdersErrors::ADD_FIELD_TYPE_ID_NOT_SET);
                        return;
                    }

                    $touristAdditionalField = OrderAdditionalFieldRepository::getTouristFieldWithId($OrderTourist, $userAdditionalField['fieldTypeId']);

                    if (is_null($touristAdditionalField)) {
                        $touristAdditionalField = new OrderAdditionalField();
                        $touristAdditionalField->bindTourist($OrderTourist);
                        if (!$touristAdditionalField->trySetAddFieldTypeId($userAdditionalField['fieldTypeId'])) {
                            $this->setError(OrdersErrors::INCORRECT_ADD_FIELD_TYPE_ID);
                            return;
                        }
                    }

                    if (!isset($additionalField['value'])) {
                        $additionalField['value'] = null;
                    }

                    $touristAdditionalField->setValue($userAdditionalField['value']);

                    if (!$touristAdditionalField->isValid()) {
                        $this->setError(OrdersErrors::ADD_FIELD_NOT_VALID);
                        return;
                    }

                    $touristAdditionalField->save(false);
                }
            }
        } catch (DocumentException $e) {
            $this->setError($e->getMessage());
        } catch (TouristException $e) {
            $this->setError($e->getMessage());
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }

        if (isset($OrderTourist)) {
            $Tourist = $OrderTourist->getTourist();

            $this->addResponse('touristId', $OrderTourist->getTouristID());
            $this->addNotificationData('comment', (string)$Tourist);

            $history = new TouristHistory();
            $history->setObjectData($Tourist);
            $history->setCommentTpl("{{touristFIO}} {{121}}{{orderId}}");
            $history->setParams([
                'touristFIO' => (string)$Tourist,
                'orderId' => $OrderModel->getOrderId()
            ]);
            $history->setActionResult(0);

            $this->addNotificationData('tourist', $Tourist->getSSTouristStructure());
        } else {
            $errorText = ErrorHelper::getErrorDescription(Yii::app()->getModule('orderService'), $this->getError());

            // запишем историю
            $history = new OrderHistory();
            $history->setObjectData($OrderModel);
            $history->setCommentTpl("{{155}}{{orderId}}, $errorText");
            $history->setParams([
                'orderId' => $OrderModel->getOrderId()
            ]);
            $history->setActionResult(1);

            $OrderModel->deleteIfEmpty();
        }

        $history->setOrderData($OrderModel);

        // сохраним результат аудита
        $this->addOrderAudit($history);
    }
}