<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/23/16
 * Time: 5:03 PM
 */
class OWMManualPermissionsDelegate extends AbstractDelegate
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

        $permissionsToCheck = [40];

        $userProfile = $params['userProfile'];

        if ($userProfile['companyID'] != $OrderModel->getAgentID()) {
            $permissionsToCheck[] = 42;
        }

        if ($userProfile['userId'] != $OrderModel->getUserID()) {
            $permissionsToCheck[] = 41;
        }

        if (!UserAccess::hasPermissions($permissionsToCheck, $params['userPermissions'])) {
            $this->setError(OrdersErrors::NOT_ENOUGH_USER_RIGHTS);
            return null;
        }
    }
}