<?php

/**
 * Активные пользователи чата
 *
 * @property $messageID    int(10) unsigned    ID сообщения
 * @property $abonentUserID    bigint(20)    Пользователь
 * @property $messageStatus    tinyint(4) NULL    Статус сообщения (0 - не отправлено , 1 - отправлено)
 *
 * @property Account $account
 * @property ChatMessage $message
 */
class ChatMessageAbonent extends CActiveRecord
{
    const STATUS_NEW = 0;
    const STATUS_READ = 1;

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'kt_chatMessageStatus';
    }

    public function bindChatMessage(ChatMessage $chatMessage)
    {
        $this->messageID = $chatMessage->getMessageId();
    }

    public function createByUser(Account $account)
    {
        $this->abonentUserID = $account->getUserId();
        $this->messageStatus = self::STATUS_NEW;
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array(
            'account' => array(self::BELONGS_TO, 'Account', 'abonentUserID'),
            'message' => array(self::BELONGS_TO, 'ChatMessage', 'messageID'),
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function confirmSending()
    {
        $this->messageStatus = 1;
    }

    public function getFromUserId()
    {
        return $this->message->getUserId();
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->abonentUserID;
    }

    public function save($runValidation = true, $attributes = null)
    {
        parent::save(false);
    }

    public function toArray()
    {
        $messageArr = $this->message->toArray();
        $messageArr['toUser'] = [
            'userId' => (integer)$this->abonentUserID,
            'firstName' => $this->account->getName(),
            'lastName' => $this->account->getSurname(),
            'sndName' => $this->account->getSndName()
        ];

        return $messageArr;
    }
}