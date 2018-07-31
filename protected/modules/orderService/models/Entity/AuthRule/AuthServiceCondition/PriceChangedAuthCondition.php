<?php

class PriceChangedAuthCondition extends AbstractAuthServiceCondition
{
    public function isValidValue($value)
    {
        return isset($value['param1']) && ($value['param1'] > 0) && ($value['param1'] <= 100);
    }

    public function isValidCondition($condition)
    {
        return $condition instanceof EqualCondition;
    }

    public function apply(OrdersServices $service)
    {
        return true;
    }
}