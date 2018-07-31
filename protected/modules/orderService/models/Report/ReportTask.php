<?php

/**
 * Создание отчета
 */
class ReportTask
{
    /**
     * @var GearmanClient
     */
    protected $gearmanClient;

    protected $serviceConfig;

    public function __construct()
    {
        // создадим гирман клиент
        $this->serviceConfig = Yii::app()->getModule('orderService')->getConfig();
        if (empty($this->serviceConfig['gearman'])) {
            throw new InvalidArgumentException('Gearman не настроен', OrdersErrors::CONFIGURATION_ERROR);
        }

        $this->gearmanClient = new GearmanClient();
        $this->gearmanClient->addServer($this->serviceConfig['gearman']['host'], $this->serviceConfig['gearman']['port']);
    }

    public function runWith(AbstractReportSpecification $specification)
    {
        $notificationGroups = NotificationGroupRepository::getNotificationGroups($specification->getEvent(), 'report', $specification->getCompany());

        $data = $specification->getTaskData();

        foreach ($notificationGroups as $notificationGroup) {
            $reportTask = [
                'templateId' => $notificationGroup->getTemplateId(),
                'data' => $data
            ];

            LogHelper::logExt(
                __CLASS__,
                __METHOD__,
                'Формирование отчета',
                'Постановка задачи на формирование отчета',
                $reportTask,
                LogHelper::MESSAGE_TYPE_INFO,
                'system.orderservice.*'
            );

            $this->gearmanClient->doBackground("{$this->serviceConfig['gearman']['workerPrefix']}_report", json_encode($reportTask));
        }
    }
}