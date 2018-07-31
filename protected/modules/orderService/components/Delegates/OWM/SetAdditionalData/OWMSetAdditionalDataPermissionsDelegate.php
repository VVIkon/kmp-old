<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 28.03.17
 * Time: 18:36
 */
class OWMSetAdditionalDataPermissionsDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_ACTION;

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