<?php

class CodeTPisDefinedAuthCondition extends AbstractAuthServiceCondition
{
    public function isValidValue($value)
    {
        return isset($value['param1']);
    }

    public function isValidCondition($condition)
    {
        return $condition instanceof InCondition
            || $condition instanceof EqualCondition
            || $condition instanceof NotEqualCondition;
    }

    public function apply(OrdersServices $service)
    {
        $failCodes = $service->getOffer()->getOfferValue()->getFailCodes();

        foreach ($failCodes as $failCode) {
            if ($this->condition->__invoke($this->value['param1'], $failCode)) {
                return true;
            }
        }

        return false;
    }
}