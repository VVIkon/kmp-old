<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/18/16
 * Time: 11:27 AM
 */
class BookChangePermissionsDelegate extends AbstractDelegate
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

        $permissionsToCheck = [24];

        $userProfile = $params['userProfile'];

        if ($userProfile['companyID'] != $OrderModel->getAgentID()) {
            $permissionsToCheck[] = 52;
        }

        if ($userProfile['userId'] != $OrderModel->getUserID()) {
            $permissionsToCheck[] = 48;
        }

        if (!UserAccess::hasPermissions($permissionsToCheck, $params['userPermissions'])) {
            $this->setError(OrdersErrors::NOT_ENOUGH_USER_RIGHTS);
            return null;
        }
    }
}