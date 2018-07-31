<?php

/**
 * Class UpdateOrderInUtkDelegate
 * Делегат для отправки информации в УТК по обновлённой заявке
 */
class UpdateOrderInUtkOLDDelegate extends WorkflowDelegate
{
    /**
     * Запуск метода выполнения действия делегата
     * @param $params
     * @return mixed
     */
    public function run($params, $module)
    {
        $namespace = $module->getConfig('log_namespace');

        $utkManager = new UtkManager($params['module'], $params['orderModule']);
        try {
            $result = $utkManager->exportOrderToUTK($params['orderId']);
        } catch (KmpException $ke) {
            LogExceptionsHelper::logExceptionEr($ke, $module, $namespace. '.errors');
        }

        return true;
    }

}

