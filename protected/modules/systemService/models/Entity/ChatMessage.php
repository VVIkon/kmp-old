<?php

/**
 * Сущность сообщения чата
 *
 * @property $messageId    int(10)
 * @property $messageDate    timestamp NULL    Время создания сообщения
 * @property $parentMessageId    int(10) unsigned NULL    ID "родительского" сообщения (на которое даётся ответ)
 * @property $orderId    bigint(20) NULL    Номер заявки, для которого публикуется сообщение
 * @property $orderNumber varchar(10) NULL	Номер заявки. Для вывода пользователю вместе с сообщением
 * @property $userId    bigint(20) NULL    Идентификатор пользователя, опубликовавшего сообщение
 * @property $messageType    varchar(45) NULL    Тип сообщения, normal/info/warning/error
 * @property $messageText    string NULL    Текст сообщения
 * @property $actions
 *
 * @property Account $account
 * @property ChatMessageAbonent[] $abonents
 */
class ChatMessage extends CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'kt_chatMessage';
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'account' => array(self::BELONGS_TO, 'Account', 'userId'),
            'abonents' => array(self::HAS_MANY, 'ChatMessageAbonent', 'messageID'),
        );
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @param $userId
     */
    public function confirmSendingToUser($userId)
    {
        if (count($this->abonents)) {
            foreach ($this->abonents as $abonent) {
                if ($abonent->getUserId() == $userId) {
                    $abonent->confirmSending();
                    $abonent->save(false);
                }
            }
        }
    }

    public function fromArray(array $data)
    {
        $this->messageText = (!empty($data['msgText'])) ? $data['msgText'] : '';
        $this->orderId = (!empty($data['orderId'])) ? $data['orderId'] : null;
        $this->orderNumber = (!empty($data['orderNumber'])) ? $data['orderNumber'] : '';
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return integer
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     *
     * @return string
     */
    public function __toString()
    {
        return $this->messageText;
    }

    /**
     *
     * @return array
     */
    public function toArray()
    {
        if (is_null($this->account)) {
            $name = '';
            $surname = '';
            $patr = '';
        } else {
            $name = $this->account->getName();
            $surname = $this->account->getSurname();
            $patr = $this->account->getSndName();
        }

        return [
            'messageId' => $this->messageId,        // ID сообщения
            'parentMessageId' => $this->parentMessageId,  // (необязательный) ID 'родительского' сообщения (на которое даётся ответ)
            'orderId' => $this->orderId,           // Номер заявки, для которого публикуется сообщение
            'orderNumber' => $this->orderNumber,
            'distribute' => null,  // определяет рассылку на специфических пользователей
            'messageType' => $this->messageType,    // (необязательный) Тип сообщения, normal/info/warning/error
            'messageText' => $this->messageText, // Текст сообщения
            'messageDate' => $this->messageDate,
            'actions' => null,           // массив sk_chatAction
            'fromUser' => [
                'userId' => (integer)$this->userId,
                'firstName' => $name,
                'lastName' => $surname,
                'sndName' => $patr
            ],
            'toUser' => null
        ];
    }
}