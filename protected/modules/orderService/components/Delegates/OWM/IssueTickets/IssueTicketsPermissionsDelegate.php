<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/10/16
 * Time: 12:18 PM
 */
class IssueTicketsPermissionsDelegate extends AbstractDelegate
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

        $permissionsToCheck = [25];

        $userProfile = $params['userProfile'];

        if ($userProfile['userId'] != $OrderModel->getUserID() && $userProfile['companyID'] == $OrderModel->getAgentID()) {
            $permissionsToCheck[] = 44;
        }

        if ($userProfile['userId'] != $OrderModel->getUserID() && $userProfile['companyID'] != $OrderModel->getAgentID()) {
            $permissionsToCheck[] = 55;
        }

        if (!UserAccess::hasPermissions($permissionsToCheck, $params['userPermissions'])) {
            $this->setError(OrdersErrors::NOT_ENOUGH_USER_RIGHTS);
            return null;
        }
    }
}