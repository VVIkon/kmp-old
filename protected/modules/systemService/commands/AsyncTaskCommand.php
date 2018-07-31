<?php

/**
 * Асинхронный запуск команд бекенда
 */
class AsyncTaskCommand extends CConsoleCommand
{
    public function init()
    {
        Yii::getLogger()->autoFlush = 1;
        Yii::getLogger()->autoDump = true;
    }

    public function actionRun()
    {
        $AsynchWorker = new AsyncWorker(Yii::app()->getModule('systemService'));
        $AsynchWorker->run();
    }
}