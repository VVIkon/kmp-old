<?php

/**
 * Обработчик подтверждения прочтения сообщения
 */
class ChatActionConfirm extends AbstractChatAction
{
    /**
     *
     * @param $from
     * @param array $data
     * @param SplObjectStorage $clients
     * @return mixed
     */
    public function process($from, array $data, SplObjectStorage $clients)
    {
        $messagesCollection = ChatMessageRepository::getMessagesForUserByMessageIds($clients->offsetGet($from), $data);
        $messagesCollection->confirmSending();
    }
}