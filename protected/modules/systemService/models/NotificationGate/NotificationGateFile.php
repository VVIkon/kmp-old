<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 19.04.17
 * Time: 18:25
 */
class NotificationGateFile extends AbstractNotificationGate
{
    protected $type = 'file';

    /**
     * @inheritdoc
     */
    public function perform($account, $subject, $text, $attachments)
    {
        echo 'Пишем в файл...', PHP_EOL;
        echo 'Заголовок: ', $subject, PHP_EOL;
        echo 'Текст: ', $text, PHP_EOL;
        echo (string)$account, PHP_EOL;
    }
}