<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 20.04.17
 * Time: 12:30
 */
class NotificationTestDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_POST_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {

    }
}