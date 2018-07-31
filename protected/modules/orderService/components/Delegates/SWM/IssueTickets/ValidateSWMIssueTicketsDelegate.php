<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/10/16
 * Time: 1:00 PM
 */
class ValidateSWMIssueTicketsDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrdersServices = new OrdersServices();
        $OrdersServices->unserialize($params['object']);

        if ($OrdersServices->inStatus(OrdersServices::STATUS_BOOKED) && $params['userProfile']['userType'] == 2) {
            $this->setError(OrdersErrors::SERVICE_STATUS_IS_BLOCKING_ISSUE_TICKETS);
            $this->addLog('Выписка билетов для услуги в статусе брони запрещена для агентов', 'error');
        }
    }
}