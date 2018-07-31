<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 12/14/16
 * Time: 12:22 PM
 */
class SetTicketsPermissionsDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_VALIDATE;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        if ($params['userProfile']['userType'] != 1) {
            $this->setError(OrdersErrors::NOT_ENOUGH_USER_RIGHTS);
            return;
        }
    }
}