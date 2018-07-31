<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 19.04.17
 * Time: 17:27
 */
class NotificationGateChat extends AbstractNotificationGate
{
    protected $type = 'chat';

    /**
     * @inheritdoc
     */
    public function perform($account, $subject, $text, $attachments)
    {
        // TODO: Implement perform() method.
    }

}