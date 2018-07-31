<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 18.04.17
 * Time: 17:24
 */
class NotificationCommand extends CConsoleCommand
{
    public function init()
    {
        Yii::getLogger()->autoFlush = 1;
        Yii::getLogger()->autoDump = true;
    }

    public function run()
    {
        $NotificationWorker = new NotificationWorker(Yii::app()->getModule('systemService'));
        $NotificationWorker->run();
    }
}