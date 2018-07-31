<?php

/**
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 22.09.17
 * Time: 11:43
 */
class ScheduleModule extends CWebModule
{
    public function init()
    {
        $this->setImport(array(
            'schedule.components.*',
        ));
    }

    public function beforeControllerAction($controller, $action)
    {
        if(parent::beforeControllerAction($controller, $action))
        {
            return true;
        }
        else
            return false;
    }

}
