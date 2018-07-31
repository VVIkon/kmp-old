<?php

class CodeTPwithoutReasonAuthCondition extends AbstractAuthServiceCondition
{
    public function isValidValue($value)
    {
        return isset($value['param1']);
    }

    public function isValidCondition($condition)
    {
        return $condition instanceof InCondition
            || $condition instanceof EqualCondition
            || $condition instanceof NotEqualCondition
            || $condition instanceof GreaterCondition
            || $condition instanceof LessCondition;
    }

    public function apply(OrdersServices $service)
    {
        // найдем пустые доп поля - причины нарушения КП
        $serviceHasEmptyReasonFailTP = false;
        $orderAddFields = OrderAdditionalFieldRepository::getServiceFieldWithId($service);
        foreach ($orderAddFields as $orderAddField) {
            if ($orderAddField->AdditionalFieldType->isReasonFailTP() && $orderAddField->isEmpty()) {
                $serviceHasEmptyReasonFailTP = true;
            }
        }

        // если есть пустые доп поля, то проверим причину нарушения КП
        if ($serviceHasEmptyReasonFailTP) {
            $failCodes = $service->getOffer()->getOfferValue()->getFailCodes();

            foreach ($failCodes as $failCode) {
                if ($this->condition->__invoke($this->value['param1'], $failCode)) {
                    return true;
                }
            }
        }

        return false;
    }

}