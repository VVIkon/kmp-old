<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 21.11.17
 * Time: 16:04
 */
class AuthServiceRepository
{
    /**
     * @param $id
     * @return AuthRule
     */
    public static function getNotComplitedService()
    {
        return AuthServiceData::model()->findAllByAttributes(['completed' => 0]);
    }

    public static function getServiceData($roundId)
    {
        return AuthServiceData::model()->findByPk($roundId);
    }

    public static function getServiceId($roundId)
    {
        return AuthServiceData::model()->findByPk($roundId);
    }

    public static function countNotCompletedServiceAuth()
    {
        return AuthServiceData::model()->count('completed = 0');
    }
}