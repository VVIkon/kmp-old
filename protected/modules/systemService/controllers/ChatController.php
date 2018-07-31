<?php

//use Ratchet\MessageComponentInterface;
//use Ratchet\ConnectionInterface;

/**
 * Class ChatController
 *
 * Обработчик чата
 *
 * DEPRECATED
 *
 * В связи с невозможностью работы Ratchet с SSL
 */
//class ChatController implements MessageComponentInterface
//{
//    protected $clients;
//    protected $accountManager;
//
//    /**
//     * Коллбек таймера для деактивации пользователя
//     */
//    public function userDeactivationCb()
//    {
//        foreach ($this->clients as $client) {
//            if (!$this->accountManager->validateToken($this->accountManager->getUserToken($this->clients[$client]))) {
//                $client->close();
//            }
//        }
//    }
//
//    public function __construct()
//    {
//        Yii::app()->db->setActive(false);
//        $this->clients = new \SplObjectStorage;
//        $this->accountManager = new AccountsMgr(Yii::app()->getModule('systemService'));
//    }
//
//    /**
//     * Обработчик открытия соединения
//     * @param ConnectionInterface $conn
//     */
//    public function onOpen(ConnectionInterface $conn)
//    {
//        Yii::app()->db->setActive(true);
//        $connectionDecorator = new ConnectionDecorator($conn);
//
//        // валидация пользователя
//        $queryParams = $conn->WebSocket->request->getQuery()->toArray();
//        if (empty($queryParams['usertoken'])) {
//            $connectionDecorator->sendError(SysSvcErrors::INVALID_TOKEN, true);
//            return;
//        }
//        // check is token valid
//        $profile = $this->accountManager->getUserIdByToken($queryParams['usertoken']);
//        if(!$profile) {
//            $connectionDecorator->sendError(SysSvcErrors::INVALID_USER, true);
//            return;
//        }
//        $Account = AccountRepository::getAccountByToken($queryParams['usertoken']);
//        if (is_null($Account)) {
//            $connectionDecorator->sendError(SysSvcErrors::INVALID_USER, true);
//            return;
//        }
//        if (!$Account->hasSubscribeToChat()) {
//            $connectionDecorator->sendError(SysSvcErrors::UNSUBSCRIBED_USER, true);
//            return;
//        }
//
//        // добавляем коннекшен
//        $this->clients->attach($conn, $Account->getUserId());
//
//        // вытаскиваем сообщения для пользователя
//        $messageCollection = ChatMessageRepository::getMessagesForUser($Account->getUserId());
//
//        $connectionDecorator->sendResponse(ConnectionDecorator::ACTION_TYPE_UNREAD_MSGS, $messageCollection->toArray());
//        Yii::app()->db->setActive(false);
//    }
//
//    /**
//     * Обработчик входяшего сообщения
//     * @param ConnectionInterface $from
//     * @param string $msg
//     */
//    public function onMessage(ConnectionInterface $from, $msg)
//    {
//        Yii::app()->db->setActive(true);
//        $connectionDecorator = new ConnectionDecorator($from);
//
//        // валидируем входные параметры
//        if (!$msg = json_decode($msg, true)) {
//            $connectionDecorator->sendError(SysSvcErrors::INVALID_MSG_STRUCTURE);
//            return;
//        }
//        if (empty($msg['actionType']) || empty($msg['data'])) {
//            $connectionDecorator->sendError(SysSvcErrors::INVALID_MSG_STRUCTURE);
//            return;
//        }
//
//        // создадим обработчик сообщения
//        if (!$ChatAction = AbstractChatAction::getByType($msg['actionType'])) {
//            $connectionDecorator->sendError(SysSvcErrors::UNKNOWN_ACTION_TYPE);
//            return;
//        }
//
//        // обработаем сообщение
//        try {
//            $ChatAction->process($from, $msg['data'], $this->clients);
//        } catch (ChatActionRightsException $e) {
//            $connectionDecorator->sendError(SysSvcErrors::NOT_ENOUGH_RIGHTS);
//            return;
//        } catch (InvalidArgumentException $e) {
//            LogHelper::logExt(
//                __CLASS__, __METHOD__,
//                'Ошибка в чате', $e->getMessage(),
//                [],
//                LogHelper::MESSAGE_TYPE_ERROR, 'system.systemservice.errors'
//            );
//
//            $connectionDecorator->sendError(SysSvcErrors::ABONENT_NOT_SET);
//            return;
//        } catch (DomainException $e) {
//            LogHelper::logExt(
//                __CLASS__, __METHOD__,
//                'Ошибка в чате', $e->getMessage(),
//                [],
//                LogHelper::MESSAGE_TYPE_ERROR, 'system.systemservice.errors'
//            );
//
//            $connectionDecorator->sendError(SysSvcErrors::FATAL_ERROR);
//            return;
//        }
//        Yii::app()->db->setActive(false);
//    }
//
//    /**
//     * Обработчик закрытия соединения
//     * @param ConnectionInterface $conn
//     */
//    public function onClose(ConnectionInterface $conn)
//    {
//        $this->clients->detach($conn);
//    }
//
//    /**
//     * Обработчик ошибок
//     * @param ConnectionInterface $conn
//     * @param Exception $e
//     */
//    public function onError(ConnectionInterface $conn, \Exception $e)
//    {
//        LogHelper::logExt(
//            __CLASS__, __METHOD__,
//            'Ошибка в чате', $e->getMessage(),
//            [],
//            LogHelper::MESSAGE_TYPE_ERROR, 'system.systemservice.errors'
//        );
//
//        $conn->close();
//    }
//}