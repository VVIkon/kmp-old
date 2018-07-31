<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/10/16
 * Time: 12:31 PM
 */
class ValidateOWMIssueTicketsBookedDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        if ($OrderModel->inStatus(OrderModel::STATUS_BOOKED) && $params['userProfile']['userType'] == 2) {
            $this->setError(OrdersErrors::ORDER_STATUS_IS_BLOCKING_ISSUE_TICKETS);
            $this->addLog('Выписка билетов для заявки в статусе брони запрещена для агентов', 'error');
        }
    }
}