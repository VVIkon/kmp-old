<?php

/**
 * базовый класс для всех делегатов
 */
abstract class AbstractDelegate
{
    /**
     * Типы делегатов
     */
    const DELEGATE_TYPE_PRE_ACTION = 1;
    const DELEGATE_TYPE_VALIDATE = 2;
    const DELEGATE_TYPE_ACTION = 4;
    const DELEGATE_TYPE_POST_ACTION = 8;

    /**
     * @var array
     */
    protected $params;
    /**
     * @var Events
     */
    protected $Event;
    /**
     * @var int
     */
    private $userId;
    /**
     * @var string
     */
    protected $type = self::DELEGATE_TYPE_ACTION;

    /**
     * Точка в хода в делегат
     * Если при выполнении предыдущих делегатов была ошибка
     * и этот делегат типа ACTION, то пропускаем его выполнение
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function process($params)
    {
        // если возникла ошибка или прерывание процесса выполнения - пропускаем все ACTION и PRE_ACTION делегаты
        if ((isset($params['breakProcess']) || $params['status']) && in_array($this->type, [self::DELEGATE_TYPE_PRE_ACTION, self::DELEGATE_TYPE_ACTION])) {
            return $params;
        }

        $this->params = $params;
        $Event = new Events();
        $Event->unserialize($params['Event']);
        $this->Event = $Event;

        if (isset($params['userProfile']) && isset($params['userProfile']['userId'])) {
            $this->userId = $params['userProfile']['userId'];
        } else {
//            throw new Exception('User ID not set');
        }

        set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($params) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Делегат', "Ошибка, Код: $errno - $errstr в $errfile на строке $errline",
                $params,
                LogHelper::MESSAGE_TYPE_ERROR, 'system.orderservice.error'
            );
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return $this->params;
        }, E_ERROR | E_NOTICE);

        // запуск делегата
        $this->run($this->params);

        restore_error_handler();

        return $this->params;
    }

    /**
     * Установка номера ошибки
     * @param $errorCode
     */
    protected function setError($errorCode, $params = '')
    {
        $this->params['status'] = $errorCode;
        $this->addLog(ErrorHelper::getErrorDescription(Yii::app()->getModule('orderService'), $errorCode), 'error', $params);
    }

    protected function breakProcess()
    {
        $this->params['breakProcess']  = true;
    }

    /**
     *
     * @return mixed
     */
    protected function getError()
    {
        return $this->params['status'];
    }

    /**
     * @return bool
     */
    protected function hasErrors()
    {
        return $this->params['status'] > 0;
    }

    /**
     * Установка ответа
     * @param $key
     * @param $value
     */
    protected function addResponse($key, $value)
    {
        if (!isset($this->params['response'])) {
            $this->params['response'] = [];
        }
        $this->params['response'][$key] = $value;
    }

    protected function mergeResponse($arr)
    {
        if (is_array($arr)) {
            if (!isset($this->params['response'])) {
                $this->params['response'] = [];
            }
            $this->params['response'] += $arr;
        }
    }

    /**
     * Проверим есть ли ответ контексте
     * @param $key
     * @return bool
     */
    protected function hasResponse($key)
    {
        return array_key_exists($key, $this->params['response']);
    }

    /**
     * Запись истории в массив SS_ORDERAUDIT
     * @param History $History
     * @param null $eventId
     */
    protected function addOrderAudit(History $History, $eventId = null)
    {
        if ($eventId) {
            $History->setEventId($eventId);
        } else {
            $History->setEventId($this->Event->getEventId());
        }

        $History->setUserIp('');
        $History->setUserId($this->userId);

        $this->params['SS_ORDERAUDIT'][] = $History->toArray();
    }

    protected function getOrderAuditArr()
    {
        if (array_key_exists('SS_ORDERAUDIT', $this->params)) {
            return $this->params['SS_ORDERAUDIT'];
        } else {
            return false;
        }
    }

    /**
     * Сохранение объекта в контексте
     * @param Serializable $Object
     */
    protected function setObjectToContext(Serializable $Object)
    {
        $this->params[get_class($Object)] = $Object->serialize();
    }

    /**
     * @param $key
     * @param $value
     */
    protected function setDataToContext($key, $value)
    {
        $this->params[$key] = $value;
    }

    /**
     * Получение объекта из контекста
     * @param $object_name string
     * @throws Exception
     * @return Serializable|false
     */
    protected function getObjectFromContext($object_name)
    {
        if (isset($this->params[$object_name])) {
            if (!class_exists($object_name)) {
                throw new Exception("Попытка получить несуществующий объект контекста $object_name");
            }

            $Object = new $object_name();

            if ($Object instanceof Serializable) {
                $Object->unserialize($this->params[$object_name]);
                return $Object;
            } else {
                throw new Exception('Объект должен быть Serializable');
            }
        } else {
            return false;
        }
    }

    /**
     * Добавление данных лога в стек
     * @param LoggerInterface $Object
     * @param $level string
     */
    protected function addLogObj(LoggerInterface $Object, $level = 'info')
    {
        $this->addLog($Object->getLogData(), $level);
    }

    /**
     * Создание уведомления
     * @param $data
     * @param $key
     */
    protected function addNotificationData($key, $data)
    {
        $this->params['notificationData'][$key] = $data;
    }

    /**
     *
     * @param $data
     * @param $templateName
     */
    protected function addNotificationTemplate($templateName, $data = [])
    {
        $this->params['notifications'][] = [
            'template' => $templateName,
            'data' => $data
        ];
    }

    /**
     * Запись в лог простого сообщения
     * @param $msg
     * @param string $level
     * @param $params []
     */
    protected function addLog($msg, $level = 'info', $params = [])
    {
        $this->params['SS_Log'][] = [
            'class' => get_class($this),
            'method' => $this->Event->getCommand(),
            'actionDescr' => '',
            'message' => $msg,
            'params' => $params,
            'level' => $level,
            'category' => "system.orderservice.$level"
        ];
    }


    /**
     * Получение данных лога
     * @return array|bool
     */
    protected function getLogArr()
    {
        if (array_key_exists('SS_Log', $this->params)) {
            return $this->params['SS_Log'];
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Точка входа для конкретного делегата
     * @param array $params
     * @return mixed
     */
    abstract public function run(array $params);
}