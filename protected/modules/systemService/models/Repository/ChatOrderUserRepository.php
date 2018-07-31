<?php

/**
 *
 */
class ChatOrderUserRepository
{
    public static function getByOrderId($orderId)
    {
        return ChatOrderUser::model()->findByPk($orderId);
    }
}