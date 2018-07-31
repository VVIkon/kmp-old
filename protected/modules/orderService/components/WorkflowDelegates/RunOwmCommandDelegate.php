<?php

/**
 * Class RunOwmCommandDelegate
 * Делегат для запуска команды OrderWorkflowManager
 */
class RunOwmCommandDelegate extends WorkflowDelegate
{
    /**
     * Запуск метода выполнения действия делегата
     * @param $params
     * @return mixed
     */
    public function run($params, $module) {

        $owm = new OrderWorkflowManager();

        return $owm->runAction($params);
    }

}

