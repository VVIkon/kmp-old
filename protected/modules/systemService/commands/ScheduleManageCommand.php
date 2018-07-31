<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 02.10.17
 * Time: 15:03
 */
class ScheduleManageCommand extends CConsoleCommand
{

    public function run()
    {
        $params['usertoken'] = '0f7369671632f427';
        $module = YII::app()->getModule('systemService');
        $scheduleMgr = $module->ScheduleMgr($module);
        $response = $scheduleMgr->runScheduler($params['usertoken']);
        if ($response){
            echo 'Команда выполнена';
        }
    }
}