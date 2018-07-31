<?php

use Workerman\Worker;

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 18.05.17
 * Time: 11:20
 */
class ChatWorkermanController
{
    /**
     * @var AccountsMgr
     */
    protected $accountManager;
    protected $clients;

    public function __construct($config)
    {
        $context = [];

        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'Чат', 'Старт',
            [
                'config' => $context
            ],
            LogHelper::MESSAGE_TYPE_INFO, 'system.systemservice.info'
        );

        // SSL context.
        if (isset($config['ssl'])) {
            $context['ssl'] = $config['ssl'];
            $context['ssl']['verify_peer'] = false;
            $context['ssl']['verify_peer_name'] = false;

            $ws_worker = new Worker("websocket://0.0.0.0:{$config['port']}", $context);
            $ws_worker->transport = 'ssl';
        } else {
            $ws_worker = new Worker("websocket://0.0.0.0:{$config['port']}");
        }

        global $argv;
        $argv[1] = 'start';

        $ws_worker->count = 1;

        $ws_worker->onConnect = function ($connection) {
            call_user_func(array($this, 'onConnect'), $connection);
        };
        $ws_worker->onMessage = function ($connection, $data) {
            call_user_func(array($this, 'onMessage'), $connection, $data);
        };
        $ws_worker->onClose = function ($connection) {
            call_user_func(array($this, 'onClose'), $connection);
        };

        $this->clients = new \SplObjectStorage;
        $this->accountManager = new AccountsMgr(Yii::app()->getModule('systemService'));
        Yii::app()->db->setActive(false);
    }

    public function run()
    {
        Worker::runAll();
    }

    protected function onConnect()
    {
        $args = func_get_args();
        $connection = $args[0];

        // добавим в connection обработчик присоединения к WebSocket
        $connection->onWebSocketConnect = function () use (&$connection) {
            call_user_func(array($this, 'onWebSocketConnect'), $connection);
        };
    }

    /**
     * Обработчик входящего сообщения
     */
    protected function onMessage()
    {
        $arr = func_get_args();
        $connection = $arr[0];
        $data = $arr[1];

        $connectionDecorator = new ConnectionWorkermanDecorator($connection);

        // валидируем входные параметры
        if (!$msg = json_decode($data, true)) {
            $connectionDecorator->sendError(SysSvcErrors::INVALID_MSG_STRUCTURE);
            return;
        }
        if (empty($msg['actionType']) || empty($msg['data'])) {
            $connectionDecorator->sendError(SysSvcErrors::INVALID_MSG_STRUCTURE);
            return;
        }

        // создадим обработчик сообщения
        if (!$ChatAction = AbstractChatAction::getByType($msg['actionType'])) {
            $connectionDecorator->sendError(SysSvcErrors::UNKNOWN_ACTION_TYPE);
            return;
        }

        // обработаем сообщение
        try {
            Yii::app()->db->setActive(true);
            $ChatAction->process($connection, $msg['data'], $this->clients);
            Yii::app()->db->setActive(false);
        } catch (ChatActionRightsException $e) {
            $connectionDecorator->sendError(SysSvcErrors::NOT_ENOUGH_RIGHTS);
            return;
        } catch (InvalidArgumentException $e) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Ошибка в чате', $e->getMessage(),
                [],
                LogHelper::MESSAGE_TYPE_ERROR, 'system.systemservice.errors'
            );

            $connectionDecorator->sendError(SysSvcErrors::ABONENT_NOT_SET);
            return;
        } catch (DomainException $e) {
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Ошибка в чате', $e->getMessage(),
                [],
                LogHelper::MESSAGE_TYPE_ERROR, 'system.systemservice.errors'
            );

            $connectionDecorator->sendError(SysSvcErrors::FATAL_ERROR);
            return;
        }
    }

    // Emitted when connection closed
    protected function onClose()
    {
        $arr = func_get_args();
        $this->clients->detach($arr[0]);
    }

    /**
     * Обработчик коннекта к вебсокету
     * Здесь идет авторизация
     */
    protected function onWebSocketConnect()
    {
        Yii::app()->db->setActive(true);
        $arr = func_get_args();
        $connectionWorkermanDecorator = new ConnectionWorkermanDecorator($arr[0]);

        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'Чат', 'Новое соединение',
            [
                'Пользователь' => $_SERVER['QUERY_STRING']
            ],
            LogHelper::MESSAGE_TYPE_INFO, 'system.systemservice.info'
        );

        // проверка корректности параметров
        if (isset($_SERVER['QUERY_STRING'])) {
            $usertokenArr = explode('=', $_SERVER['QUERY_STRING']);
            if (count($usertokenArr) != 2 || $usertokenArr[0] != 'usertoken') {
                $connectionWorkermanDecorator->sendError(SysSvcErrors::INVALID_USER, true);
                LogHelper::logExt(
                    __CLASS__, __METHOD__,
                    'Ошибка в чате', 'Не указан токен',
                    [
                        'массив с токеном' => $usertokenArr
                    ],
                    LogHelper::MESSAGE_TYPE_ERROR, 'system.systemservice.errors'
                );
                return;
            } else {
                $usertoken = $usertokenArr[1];
            }
        } else {
            $connectionWorkermanDecorator->sendError(SysSvcErrors::FATAL_ERROR, true);
            return;
        }

        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'Чат', 'Пытаемся авторизовать пользователя',
            [
                'токен' => $usertoken
            ],
            LogHelper::MESSAGE_TYPE_INFO, 'system.systemservice.info'
        );

        // авторизация
        if (!$this->accountManager->userHasAccessToChat($usertoken)) {
            $connectionWorkermanDecorator->sendError(SysSvcErrors::INVALID_USER, true);
            LogHelper::logExt(
                __CLASS__, __METHOD__,
                'Ошибка в чате', 'Пользователь не найден или не подписан',
                [],
                LogHelper::MESSAGE_TYPE_ERROR, 'system.systemservice.errors'
            );
            return;
        }

        $Account = AccountRepository::getAccountByToken($usertoken);

        LogHelper::logExt(
            __CLASS__, __METHOD__,
            'Чат', 'Авторизация успешна',
            [
                'пользователь' => (string)$Account
            ],
            LogHelper::MESSAGE_TYPE_INFO, 'system.systemservice.info'
        );

        // добавляем коннекшен
        $this->clients->offsetSet($arr[0], $Account->getUserId());

        // вытаскиваем сообщения для пользователя
        $messageCollection = ChatMessageRepository::getMessagesForUser($Account->getUserId());

        $connectionWorkermanDecorator->sendResponse(ConnectionWorkermanDecorator::ACTION_TYPE_UNREAD_MSGS, $messageCollection->toArray());
        Yii::app()->db->setActive(false);
    }
}
