<?php

/**
 * Класс репозиторий для истории заявок
 * User: rock
 * Date: 8/12/16
 * Time: 5:32 PM
 */
class OrderHistoryRepository
{

    /**
     * Получение истории заявки по языку и коду заявки
     * @param $lang
     * @param $orderId
     * @return array
     */
    public static function getByOrderIdAndLang($orderId, $lang)
    {
        $Histories = History::model()->with(
            array('event', 'User', 'event.EventMessage' => array('condition' => "lang = '$lang'"))
        )->findAll(array(
            'condition' => 'orderId=:orderId',
            'params' => array(':orderId' => $orderId),
            'order' => 'id DESC'
        ));

        $answer = [];

        if (!is_null($Histories) && count($Histories)) {
            $msg_numbers = [];
            $mustacheParams = [];

            // найдем все сообщения из таблицы kt_messages и выберем скопом
            foreach ($Histories as $History) {
                $msg_numbers = array_merge($msg_numbers, $History->getMessageNumbers());
            }

            array_unique($msg_numbers);

            // выберем все сообщения из таблицы сообщений
            $Messages = MessageRepository::getByIdsAndLang($msg_numbers, $lang);

            // сделаем соответствия для усов - код шаблончика - сообщение
            if ($Messages && count($Messages)) {
                foreach ($Messages as $Message) {
                    $mustacheParams[$Message->getMsgCode()] = $Message->getMessage();
                }
            }

            foreach ($Histories as $History) {
                $answer[] = $History->getOrderHistoryWithMsgParams($mustacheParams);
            }
        }

        return $answer;
    }
}