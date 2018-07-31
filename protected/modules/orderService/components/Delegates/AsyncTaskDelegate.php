<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 11/15/16
 * Time: 12:48 PM
 */
class AsyncTaskDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_POST_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        // попробуем найти задачу
        $AsyncTask = $this->getObjectFromContext('AsyncTask');

        // задча не нашлась
        if (!$AsyncTask) {
            return null;
        }

        $AsyncTask->run();
    }
}