<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/16/16
 * Time: 3:58 PM
 */
class PayStartPermissionsDelegate extends AbstractDelegate
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

        $permissionsToCheck = [22];

        $userProfile = $params['userProfile'];

        if ($userProfile['userId'] != $OrderModel->getUserID()) {
            $permissionsToCheck[] = 47;
        }

        if ($userProfile['companyID'] != $OrderModel->getAgentID()) {
            $permissionsToCheck[] = 51;
        }

        if (!UserAccess::hasPermissions($permissionsToCheck, $params['userPermissions'])) {
            $this->setError(OrdersErrors::NOT_ENOUGH_USER_RIGHTS);
            return null;
        }
    }
}