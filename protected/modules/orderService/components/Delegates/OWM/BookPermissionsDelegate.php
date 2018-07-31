<?php

/**
 * Права доступа:
    PERMISSION_21
    OR PERMISSION_46 (если пользователь не создатель заявки)
    OR PERMISSION_50 (если компания пользователя не равна компании заявки)
 */
class BookPermissionsDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        $permissionsToCheck = [21];

        $userProfile = $params['userProfile'];

        if ($userProfile['userId'] != $OrderModel->getUserID() && $userProfile['companyID'] == $OrderModel->getAgentID()) {
            $permissionsToCheck[] = 46;
        }

        if ($userProfile['userId'] != $OrderModel->getUserID() && $userProfile['companyID'] != $OrderModel->getAgentID()) {
            $permissionsToCheck[] = 50;
        }

        if (!UserAccess::hasPermissions($permissionsToCheck, $params['userPermissions'])) {
            $this->setError(OrdersErrors::NOT_ENOUGH_USER_RIGHTS);
            return null;
        }
    }
}