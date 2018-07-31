<?php

abstract class AbstractAuthServiceCondition
{
    protected $comment;
    protected $value;
    /**
     * @var callable
     */
    protected $condition;
    protected $conditionSign;
    protected $conditionName;

    abstract public function isValidValue($value);

    abstract public function isValidCondition($condition);

    abstract public function apply(OrdersServices $service);

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function setCondition(callable $cond)
    {
        $this->condition = $cond;
    }

    public function setConditionSign($sign)
    {
        $this->conditionSign = $sign;
    }

    public function setConditionName($name)
    {
        $this->conditionName = $name;
    }

    /**
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'conditionName' => $this->conditionName,
            'comment' => $this->comment,
            'condition' => $this->conditionSign,
            'value' => $this->value
        ];
    }
}