<?php

/**
 * Класс формирует очередь сообщений и делает их рассылку
 */
class MsgSender
{
    /**
     * @var SplObjectStorage
     */
    protected $clients;

    /**
     * @var ChatMessageAbonent []
     */
    protected $chatMessagesToSend = [];

    /**
     * ID пользователей, которые не получили сообщения
     * @var array
     */
    protected $offlineUsers = [];


    /**
     * MsgSender constructor.
     * @param SplObjectStorage $clients
     */
    public function __construct(SplObjectStorage $clients)
    {
        $this->clients = $clients;
    }

    /**
     * Добавление сообщения в очередь отправки
     * @param ChatMessageAbonent $chatMessageAbonent
     */
    public function addMessageToQueue(ChatMessageAbonent $chatMessageAbonent)
    {
        $this->chatMessagesToSend[] = $chatMessageAbonent;
    }

    /**
     * @return array
     */
    public function getOfflineUsers()
    {
        return $this->offlineUsers;
    }

    /**
     * Отправка сообщений из очереди
     */
    public function sendMsgs()
    {
        $allUsers = [];
        $onlineUsers = [];

        // список всех, кому надо отправить письмо
        foreach ($this->chatMessagesToSend as $chatMessageToSend) {
            $allUsers[] = $chatMessageToSend->getUserId();
        }

        foreach ($this->clients as $client) { // переберем всех клиентов
            $onlineUsers[] = $this->clients[$client]; // составим список всех онлайн клиентов

            foreach ($this->chatMessagesToSend as $chatMessageToSend) {
                if ($this->clients[$client] == $chatMessageToSend->getUserId()) {
//                    // ставим подтверждение прочтения только отправителю
//                    if ($this->clients[$client] == $chatMessageToSend->getFromUserId()) {
//                        $chatMessageToSend->confirmSending();
//                        $chatMessageToSend->save();
//                    }
                    $connectionDecorator = new ConnectionWorkermanDecorator($client);
                    $connectionDecorator->sendResponse(ConnectionDecorator::ACTION_TYPE_MSG, $chatMessageToSend->toArray());
                }
            }
        }

        $this->offlineUsers = array_diff($allUsers, $onlineUsers);
    }
}