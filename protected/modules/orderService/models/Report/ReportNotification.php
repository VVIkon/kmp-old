<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 20.09.17
 * Time: 12:45
 */
class ReportNotification
{
    protected $gearmanClient;
    protected $conf;

    public function __construct($gearmanConf)
    {
        $this->conf = $gearmanConf;
        $this->gearmanClient = new GearmanClient();
        $this->gearmanClient->addServer($gearmanConf['host'], $gearmanConf['port']);
    }

    public function sendReportNotification($email, $subject, $companyId, $filename)
    {
        $event = EventRepository::getByEventName('SENDDOCUMENT');

        if (is_null($event)) {
            throw new ReportException('Не найдено событие SENDDOCUMENT');
        }

        $eventNotificationGroups = NotificationGroupRepository::getNotificationGroups($event, 'sendfile', $companyId);

        foreach ($eventNotificationGroups as $eventNotificationGroup) {
            // сформируем ss_userNotification
            $ss_userNotification = [
                'templateId' => $eventNotificationGroup->getTemplateId(),
                'types' => [1], // только email
                'users' => [],
                'address' => $email,
                'createdAt' => StdLib::getMysqlDateTime(),
                'data' => [
                    'attachments' => [
                        [
                            'url' => $filename
                        ]
                    ],
                    'event' => $event->toArray(),
                    'subject' => $subject
                ]
            ];

            // заполнение данными и отправка
            $this->gearmanClient->doBackground("{$this->conf['workerPrefix']}_notification", json_encode($ss_userNotification));
        }
    }
}