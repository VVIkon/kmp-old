<?php

/**
 * Справочник ролей пользователей
 */
class UserRolesDictionaryHandler extends AbstractDictionaryHandler
{
    /**
     * Получение справочних данных
     * @param array $params
     * @return array
     */
    public function getDictionaryData($params)
    {
        $roles = Yii::app()->db->createCommand()
          ->select(implode(',', [
              'RoleID as roleId',
              'PermissionsCode as permissionsCode', 
              'PermissionsName as permissionsName'
            ]))
          ->from('kt_users_permissions')
          ->queryAll();

        $permissions = Yii::app()->db->createCommand()
          ->select(implode(',', [
              'BinaryDigit as bit',
              'OperationName as permission'
            ]))
          ->from('kt_ref_permissions')
          ->queryAll();

        return [
            'roles' => $roles,
            'permissions' => $permissions
        ];
    }

}