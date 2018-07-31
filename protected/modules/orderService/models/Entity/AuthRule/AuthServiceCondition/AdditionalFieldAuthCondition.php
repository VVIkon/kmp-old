<?php

class AdditionalFieldAuthCondition extends AbstractAuthServiceCondition
{
    public function isValidValue($value)
    {
        if (isset($value['param1'])) {
            $addFieldType = AdditionalFieldTypeRepository::getById($value['param1']);

            if (is_null($addFieldType)) {
                return false;
            }
        } else {
            return false;
        }

        if (isset($value['param2'])) {
            if (!$addFieldType->isValuePossible($value['param2'])) {
                return false;
            }
        } else {
            return false;
        }

        return true;
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
        $addField = OrderAdditionalFieldRepository::getServiceFieldWithId($service, $this->value['param1']);
        return $this->condition->__invoke($this->value['param2'], $addField->getValue());
    }
}