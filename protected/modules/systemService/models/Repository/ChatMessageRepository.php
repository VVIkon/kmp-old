<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 16.02.17
 * Time: 15:03
 */
class ChatMessageRepository
{
    /**
     * Получение очереди сообщений для пользователя
     * @param $userId
     * @return ChatMessageCollection
     */
    public static function getMessagesForUser($userId)
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition('messageStatus = 0');
        $criteria->addCondition("abonentUserID = $userId");

        $messages = ChatMessageAbonent::model()->with(array('message.account' => ['alias' => 'm_author'], 'account'))->findAll($criteria);

        return new ChatMessageCollection($messages);
    }

    public static function getMessagesForUserByMessageIds($userId, array $msgIds)
    {
        $msgIdsStr = implode(',', $msgIds);

        $criteria = new CDbCriteria();
        $criteria->addCondition('messageStatus = 0');
        $criteria->addCondition("messageId IN ($msgIdsStr)");
        $criteria->addCondition("abonentUserID = $userId");

        $messages = ChatMessageAbonent::model()->with()->findAll($criteria);

        return new ChatMessageCollection($messages);
    }

    /**
     * Получение истории сообщений
     * @param $orderId
     * @param $fromTime
     * @param $limit
     * @return ChatMessageCollection
     */
    public static function getOrderMessagesHistory($orderId, DateTime $fromTime, $limit)
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition("messageDate < \"{$fromTime->format('Y-m-d H:i:s')}\"");
        $criteria->addCondition("message.orderId = $orderId");
        $criteria->order = 'messageDate DESC';
        $criteria->limit = $limit;

        $messages = ChatMessageAbonent::model()->with(array('message.account' => ['alias' => 'm_author'], 'account'))->findAll($criteria);
        return new ChatMessageCollection($messages);
    }

    /**
     * Получение истории сообщений
     * @param $userId
     * @param $fromTime
     * @param $limit
     * @return ChatMessageCollection
     */
    public static function getUserMessagesHistory($userId, DateTime $fromTime, $limit)
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition("messageDate < \"{$fromTime->format('Y-m-d H:i:s')}\"");
        $criteria->addCondition("message.userId = $userId OR abonentUserID = $userId");
        $criteria->addCondition("message.orderId IS NULL");
        $criteria->order = 'messageDate DESC';
        $criteria->limit = $limit;

        $messages = ChatMessageAbonent::model()->with(array('message.account' => ['alias' => 'm_author'], 'account'))->findAll($criteria);

        return new ChatMessageCollection($messages);
    }
}