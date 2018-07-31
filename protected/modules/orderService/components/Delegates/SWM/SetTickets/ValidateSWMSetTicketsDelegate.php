<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 12/14/16
 * Time: 12:37 PM
 */
class ValidateSWMSetTicketsDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if (!isset($params['ticketData']['ticketAction'])
            || !isset($params['ticketData']['ticketData'])
            || !isset($params['ticketData']['ticketData']['pnr'])
            || !isset($params['ticketData']['ticketData']['touristId'])
            || !isset($params['ticketData']['ticketData']['ticketNumber'])
            || !isset($params['ticketData']['ticketData']['ticketStatus'])
        ) {
            $this->addLog('Валидация входных параметров SWM', 'warning');
            $this->setError(OrdersErrors::INPUT_PARAMS_ERROR);
            return;
        }
    }
}