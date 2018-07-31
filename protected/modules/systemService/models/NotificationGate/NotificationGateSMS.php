<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 19.04.17
 * Time: 18:24
 */
class NotificationGateSMS extends AbstractNotificationGate
{
    protected $type = 'sms';

    /**
     * @inheritdoc
     */
    public function perform($account, $subject, $text, $attachments)
    {
        // TODO: Implement perform() method.
    }
}