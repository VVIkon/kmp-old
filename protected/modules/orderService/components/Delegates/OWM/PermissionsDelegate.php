<?php

/**
 * Права доступа:
    PERMISSION_20 если пользователь равен создателю заявки OR
    ИЛИ PERMISSION_45 (если пользователь не равен создателю заявки)
    ИЛИ PERMISSION_49 (если компания пользователя не равна компании заявки)
 */
class PermissionsDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    public function run(array $params)
    {
        $OrderModel = new OrderModel();
        $OrderModel->unserialize($params['object']);

        $permissionsToCheck = [20];

        $userProfile = $params['userProfile'];

        if ($userProfile['userId'] != $OrderModel->getUserID() && $userProfile['companyID'] == $OrderModel->getAgentID()) {
            $permissionsToCheck[] = 45;
        }

        if ($userProfile['userId'] != $OrderModel->getUserID() && $userProfile['companyID'] != $OrderModel->getAgentID()) {
            $permissionsToCheck[] = 49;
        }

        if (!UserAccess::hasPermissions($permissionsToCheck, $params['userPermissions'])) {
            $this->setError(OrdersErrors::NOT_ENOUGH_USER_RIGHTS);
            return null;
        }
    }
}