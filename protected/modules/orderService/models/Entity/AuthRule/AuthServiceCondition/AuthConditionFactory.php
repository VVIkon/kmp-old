<?php

class AuthConditionFactory
{
    /**
     * @param $so_authServiceCondition
     * @throws AuthConditionException
     * @return AbstractAuthServiceCondition
     */
    public static function createFromArray($so_authServiceCondition)
    {
        // conditionName
        if (isset($so_authServiceCondition['conditionName'])) {
            $className = ucfirst($so_authServiceCondition['conditionName']) . 'AuthCondition';

            if (class_exists($className)) {
                $authServiceConditionClass = new $className();
                $authServiceConditionClass->setConditionName($so_authServiceCondition['conditionName']);
            } else {
                throw new InvalidArgumentException("ConditionName: {$so_authServiceCondition['conditionName']} incorrect", OrdersErrors::AUTH_CONDITION_CREATION_ERROR);
            }
        } else {
            throw new InvalidArgumentException('ConditionName is empty', OrdersErrors::AUTH_CONDITION_CREATION_ERROR);
        }

        // value
        if (isset($so_authServiceCondition['value'])) {
            if ($authServiceConditionClass->isValidValue($so_authServiceCondition['value'])) {
                $authServiceConditionClass->setValue($so_authServiceCondition['value']);
            } else {
                throw new InvalidArgumentException("<{$so_authServiceCondition['conditionName']}> Invalid value", OrdersErrors::AUTH_CONDITION_CREATION_ERROR);
            }
        } else {
            throw new InvalidArgumentException("<{$so_authServiceCondition['conditionName']}> Value is empty", OrdersErrors::AUTH_CONDITION_CREATION_ERROR);
        }

        // comment
        if (!empty($so_authServiceCondition['comment'])) {
            $authServiceConditionClass->setComment($so_authServiceCondition['comment']);
        }

        // condition
        if (isset($so_authServiceCondition['condition'])) {
            $condition = ConditionFactory::createFromString($so_authServiceCondition['condition']);

            if ($authServiceConditionClass->isValidCondition($condition)) {
                $authServiceConditionClass->setCondition($condition);
                $authServiceConditionClass->setConditionSign($so_authServiceCondition['condition']);
            } else {
                throw new InvalidArgumentException("<{$so_authServiceCondition['condition']}> unsupported condition: {$so_authServiceCondition['condition']}", OrdersErrors::AUTH_CONDITION_CREATION_ERROR);
            }
        } else {
            throw new InvalidArgumentException("<{$so_authServiceCondition['condition']}> Condition is empty", OrdersErrors::AUTH_CONDITION_CREATION_ERROR);
        }

        return $authServiceConditionClass;
    }
}