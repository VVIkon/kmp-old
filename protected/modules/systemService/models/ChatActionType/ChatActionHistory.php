<?php

/**
 * ws://localhost:8100?usertoken=70622e30e3d24c69
 *
 *
 * Msg to User
 {
  "actionType": "history",
  "data":
  {
  "from": "2017-09-01 18:00:00",
  "orderId": ""
  }
 }
 */
class ChatActionHistory extends AbstractChatAction
{
    /**
     * @param $from
     * @param array $data
     * @param SplObjectStorage $clients
     * @return mixed
     */
    public function process($from, array $data, SplObjectStorage $clients)
    {
        if (!isset($data['orderId'])) {
            $data['orderId'] = null;
        }
        if (empty($data['from'])) {
            throw new InvalidArgumentException('Не задано время отсчета истории');
        }
        $config = Yii::app()->getModule('systemService')->getConfig();

        if (empty($config['chat']['historyMessagesNumber'])) {
            throw new InvalidArgumentException('Не указано количество сообщений в истории чата (historyMessagesNumber)');
        }

        $connectionDecorator = new ConnectionWorkermanDecorator($from);
        $messagesStartTime = new DateTime($data['from']);
        $answer = [];

        if ($data['orderId']) {
            $messages = ChatMessageRepository::getOrderMessagesHistory($data['orderId'], $messagesStartTime, $config['chat']['historyMessagesNumber']);
        } else {
            $messages = ChatMessageRepository::getUserMessagesHistory($clients->offsetGet($from), $messagesStartTime, $config['chat']['historyMessagesNumber']);
        }

        if (count($messages)) {
            $answer = $messages->toArray();
        }

        $connectionDecorator->sendResponse(ConnectionWorkermanDecorator::ACTION_TYPE_HISTORY, $answer);
    }
}