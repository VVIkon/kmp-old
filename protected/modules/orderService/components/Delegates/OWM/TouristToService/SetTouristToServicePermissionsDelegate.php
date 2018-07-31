<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/29/16
 * Time: 11:43 AM
 */
class SetTouristToServicePermissionsDelegate extends AbstractDelegate
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

        $permissionsToCheck = [20];

        $userProfile = $params['userProfile'];

        if ($userProfile['companyID'] != $OrderModel->getAgentID()) {
            $permissionsToCheck[] = 49;
        }

        if ($userProfile['userId'] != $OrderModel->getUserID()) {
            $permissionsToCheck[] = 45;
        }

        if (!UserAccess::hasPermissions($permissionsToCheck, $params['userPermissions'])) {
            $this->setError(OrdersErrors::NOT_ENOUGH_USER_RIGHTS);
            return null;
        }
    }

}