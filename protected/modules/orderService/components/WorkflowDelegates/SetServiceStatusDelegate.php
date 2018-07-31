<?php

/**
 * Class SetServiceStatusDelegate
 * Делегат для установки статуса услуги заявки
 */
class SetServiceStatusDelegate extends WorkflowDelegate
{
    /**
     * Запуск метода выполнения действия делегата
     * @param $params
     * @return mixed
     */
    public function run($params, $module) {

        $service = new Service();
        $service->load($params['serviceId']);

        $service->status = ServicesForm::SERVICE_STATUS_DONE;
        $service->save();

        return ['serviceStatus' => $service->status];
    }

}

