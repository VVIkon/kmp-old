<?php

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 *
 * @property $eventId
 * @property $eventTime	timestamp NULL	Дата/время изменения
 * @property $objectType	varchar(100) NULL	Тип объекта, для которого записывается аудит = order/service/tourist
 * @property $orderId	bigint(20) NULL	Идентификатор (номер) заявки
 * @property $objectId	bigint(20) NULL	Идентификатор аудируемого объекта, для заявки здесь дублируется ID заявки
 * @property $userId	bigint(20) NULL	Идентификатор пользователя, который произвёл изменение
 * @property $actionResult	int(11) NULL	Результат действия, 0 = Оk, остальное = Ошибка
 * @property $orderStatus	int(11) NULL	Статус заявки после выполнения действия
 * @property $objectStatus	int(11) NULL	Статус объекта после выполнения действия
 * @property $commentTpl	varchar(255) NULL	Шаблон комментария
 * @property $commentParams	text NULL	Параметры для комментария
 * @property $userIp	varchar(15) NULL	IP адрес пользователя, инициировавшего изменение
 *
 * @property Account $User
 * @property Events $event
 */
class History extends CActiveRecord
{
    protected $language;
    protected $orderId;

    public function tableName()
    {
        return 'kt_order_history';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    public function relations()
    {
        return array(
            'event' => array(self::BELONGS_TO, 'Events', 'eventId'),
            'User' => array(self::BELONGS_TO, 'Account', 'userId')
        );
    }

    public function init()
    {
        $this->eventTime = StdLib::getMysqlDateTime();
    }

    /**
     * @param mixed $objectType
     */
    protected function setObjectType($objectType)
    {
        $this->objectType = $objectType;
    }

    /**
     * @param mixed $objectId
     */
    protected function setObjectId($objectId)
    {
        $this->objectId = $objectId;
    }

    /**
     * @param mixed $objectStatus
     */
    protected function setObjectStatus($objectStatus)
    {
        $this->objectStatus = $objectStatus;
    }

    /**
     * @param mixed $eventId
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;
    }

    /**
     * @param mixed $actionResult
     */
    public function setActionResult($actionResult)
    {
        $this->actionResult = $actionResult;
    }

    /**
     * @param mixed $template
     */
    public function setCommentTpl($template)
    {
        $this->commentTpl = $template;
    }

    /**
     * @param mixed $template
     */
    public function setSuccessTpl($template)
    {
        $this->actionResult = 0;
        $this->commentTpl = $template;
    }

    /**
     * @param mixed $template
     */
    public function setFailTpl($template)
    {
        $this->actionResult = 1;
        $this->commentTpl = $template;
    }

    public function getMessageNumbers()
    {
        $matches = [];
        $expr = '/{{(\d{1,})}}/';

        if (preg_match_all($expr, $this->commentTpl, $matches)) {
            return $matches[1];
        } else {
            return [];
        }
    }

    /**
     * @param array $commentParams
     */
    public function setParams(array $commentParams)
    {
        $this->commentParams = json_encode($commentParams);
    }

    /**
     * @param array $commentParams
     */
    public function setCommentParams(array $commentParams)
    {
        $this->commentParams = json_encode($commentParams);
    }

    /**
     * @return array
     */
    public function getCommentParams()
    {
        return json_decode($this->commentParams, true);
    }

    /**
     * @param mixed $userIp
     */
    public function setUserIp($userIp)
    {
        $this->userIp = $userIp;
    }

    /**
     * Инициализация истории заявкой
     * @param OrderModel $OrderModel
     * @throws Exception
     */
    public function setOrderData(OrderModel $OrderModel)
    {
        if (!$OrderModel->getOrderId()) {
            throw new Exception('Пытаемся сделать аудит несуществующей заявки');
        }

        $this->setOrderId($OrderModel->getOrderId());
        $this->setOrderStatus($OrderModel->getStatus());
    }

    /**
     * Преобразование в массив
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    public function fromArray($arr)
    {
        $this->setAttributes($arr, false);
    }

    /**
     * @param mixed $lang
     */
    public function setLanguage($lang)
    {
        $this->language = $lang;
    }

    /**
     * @param mixed $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @param mixed $orderStatus
     */
    public function setOrderStatus($orderStatus)
    {
        $this->orderStatus = $orderStatus;
    }

    /**
     * Получение истории заявок в виде массива
     * @param $eventCommentParams
     * @return array
     */
    public function getOrderHistoryWithMsgParams($eventCommentParams = [])
    {
        $answer = [
            'eventTime' => $this->eventTime,
            'serviceId' => null,                             // Идентификатор услуги, если не заполнен, действие/событие относится к заявке
            'serviceName' => null,                             // Название услуги
            'eventId' => $this->eventId,                     // Идентификатор события из Реестра событий системы
            'event' => null,                               // Название события из Реестра событий системы
            'result' => '',                                // Результат выполнения 0 - все ОК/1 - ошибка
            'orderId' => $this->orderId,              // Статус заявки после события
            'orderStatus' => $this->orderStatus,              // Статус заявки после события
            'serviceStatus' => null,           // Статус услуги после события
            'userName' => null,                      // Пользователь выполнивший изменения
            'eventComment' => null,
        ];

        if ($this->objectType == 'service') {
            $answer['serviceId'] = $this->objectId;

            $OrdersServices = OrdersServices::model()->findByPk($this->objectId);

            if ($OrdersServices) {
                $answer['serviceName'] = $OrdersServices->getServiceName();
                $answer['serviceStatus'] = $this->objectStatus;
            }
        }

        if (!is_null($this->event) && !is_null($this->event->EventMessage)) {
            $answer['event'] = $this->event->EventMessage->getMessage();
        }

        $params = $eventCommentParams;

        if (is_array($this->getCommentParams())) {
            $params = $eventCommentParams + $this->getCommentParams();
        }

        $m = new Mustache_Engine();
        $answer['eventComment'] = $m->render($this->commentTpl, $params);

        $answer['result'] = $this->actionResult;

        if ($this->getUser()) {
            $answer['userName'] = $this->getUser()->getFI();
        } else {
            $answer['userName'] = '';
        }

        return $answer;
    }

    /**
     * @return Account|null
     */
    public function getUser()
    {
        return $this->User;
    }

    static public function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('orderId', new Assert\NotBlank(array('message' => OrdersErrors::ORDER_ID_NOT_SET)));
        $metadata->addPropertyConstraint('language', new Assert\NotBlank(array('message' => OrdersErrors::LANGUAGE_NOT_SET)));
        $metadata->addPropertyConstraint('language', new Assert\Language(array('message' => OrdersErrors::INCORRECT_LANGUAGE)));
    }
}