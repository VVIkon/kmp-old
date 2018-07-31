<?php

/**
 * Class ServiceUser
 * Реализует функциональность для работы с данными системного пользователя сервиса
 */
class ServiceUser
{

    const SERVICE_USER_ID = 33;

    /**
     * Получить токен пользователя сервиса
     * @param $typeCode
     * @return bool
     */
    public static function getToken()
    {
        $command = Yii::app()->db->createCommand()
            ->select('*')
            ->from('kt_users_tokens')
            ->where('kt_users_tokens.UserID = :userId', array(':userId' => self::SERVICE_USER_ID));

        try {
            $tokenInfo = $command->queryRow();
        } catch (Exception $e) {
            throw new KmpDbException(
                get_class(), __FUNCTION__,
                OrdersErrors::CANNOT_GET_USER_TOKEN,
                $command->getText(),
                $e
            );
        }

        return isset($tokenInfo['token']) ? $tokenInfo['token'] : false;

    }

}

