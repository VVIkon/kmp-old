<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 7/25/16
 * Time: 12:31 PM
 */
class RunCacheClear extends CConsoleCommand
{
    public function init()
    {
        Yii::getLogger()->autoFlush = 1;
        Yii::getLogger()->autoDump = true;
    }

    public function actionClear()
    {
        $module = YII::app()->getModule('searcherService');
        $time_diff = $module->getConfig('cacheClear');

        if (!$time_diff) {
            exit(1);
        }

        $DateTime = new DateTime();
        if (false === $DateTime->sub(new DateInterval($time_diff))) {
            exit(2);
        }

        $command = Yii::app()->db->createCommand();
        $deleted_rows = $command->delete('token_cache', 'token_cache.StartDateTime < :startTime', array(':startTime' => $DateTime->format('Y-m-d H:i:s')));

        // Очистка кеша расписания
        $expireAviaSchedule = StdLib::nvl($module->getConfig('expireAviaSchedule'), 10);
        $deleted_rows2 = ScheduleCacheRepository::delExpireScheduleCache($expireAviaSchedule);
        exit("Успех, удалено $deleted_rows записей, удалено $deleted_rows2 расписаний.");
    }
}