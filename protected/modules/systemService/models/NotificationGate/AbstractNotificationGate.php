<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 19.04.17
 * Time: 17:27
 */
abstract class AbstractNotificationGate
{
    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     *
     * @param Account $account
     * @param $text
     * @param $subject
     * @param $attachments
     * @return mixed
     */
    abstract public function perform($account, $subject, $text, $attachments);
}