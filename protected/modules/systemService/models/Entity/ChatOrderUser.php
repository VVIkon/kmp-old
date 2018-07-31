<?php

/**
 * Пользователи, участвующие в обсуждении заявки
 *
 * @property $orderId
 * @property $abonentUserId
 */
class ChatOrderUser extends CActiveRecord
{
    public function tableName()
    {
        return 'kt_orderChatUsers';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @param mixed $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @return array
     */
    public function getUserIds()
    {
        return json_decode($this->abonentUserId);
    }

    public function addUser($userId)
    {
        $userIds = json_decode($this->abonentUserId);
        $userIds[] = (integer)$userId;
        $userIds = array_unique($userIds);
        $this->abonentUserId = json_encode($userIds);
    }

    public function save($runValidation=true,$attributes=null)
    {
        parent::save(false);
    }
}