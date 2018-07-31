<?php

/**
 * Created by PhpStorm.
 * User: rock
 * Date: 18.04.17
 * Time: 17:44
 */
class NotificationDelegate extends AbstractDelegate
{
    protected $type = self::DELEGATE_TYPE_POST_ACTION;

    /**
     * @param array $params
     * @return mixed
     */
    public function run(array $params)
    {
        // вытащим заявку и услугу, если такие есть в контексте
        $OrderModel = new OrderModel();

        if (isset($params['orderModel'])) {
            $OrderModel->unserialize($params['orderModel']);
            $service = new OrdersServices();
            $service->unserialize($params['object']);
            $service->refresh();
        } else {
            $OrderModel->unserialize($params['object']);
            $OrderModel->refresh();
            $service = null;
        }

        // добавим базовое уведомление в набор для отправки
        $baseNotification = ($this->hasErrors()) ? 'error' : 'success';
        $baseNotificationData = isset($params['notificationData']) ? $params['notificationData'] : [];
        if ($baseNotification == 'error') {
            $baseNotificationData['comment'] = ErrorHelper::getErrorDescription(Yii::app()->getModule('orderService'), $this->getError());
        }
        $params['notifications'][] = [
            'template' => $baseNotification,
            'data' => $baseNotificationData
        ];

        $this->Event->setLang('ru');

        // создадим гирман клиент
        $serviceConfig = Yii::app()->getModule('orderService')->getConfig();
        if (empty($serviceConfig['gearman'])) {
            $this->setError(OrdersErrors::ORDER_SERVICE_FATAL_ERROR);
            return;
        }

        $GearmanClient = new GearmanClient();
        $GearmanClient->addServer($serviceConfig['gearman']['host'], $serviceConfig['gearman']['port']);

        // начнем ставить уведомления в очередь
        foreach ($params['notifications'] as $notification) {
            $EventNotificationGroups = NotificationGroupRepository::getNotificationGroups($this->Event, $notification['template'], $OrderModel->getCompany());

            if (empty($EventNotificationGroups)) {
                continue;
            }

            // добавим данные для шаблонизации
            $notificationData = array_merge($notification['data'], [
                'event' => $this->Event->toArray(),
                'order' => $OrderModel->getSOOrder(),
                'service' => (is_null($service)) ? null : $service->getSLOrderService(),
                'environment' => [
                    'baseUrl' => $serviceConfig['baseUrl']
                ]
            ]);

            foreach ($EventNotificationGroups as $eventNotificationGroup) {
                $notificationGroup = $eventNotificationGroup->getNotificationGroup();
                // отправим уведомления по группам
                // users to notify
                if(is_null($notificationGroup)){
                    $this->addLog("Не выбраны уведомления по группе  {$this->Event->getEventId()}");
                    return;
                }
                $users = $notificationGroup->getUsersToNotifyFromOrder($OrderModel);

                // types
                $types = [];
                if ($notificationGroup->hasEmail()) {
                    $types[] = 1;
                }
                if ($notificationGroup->hasSMS()) {
                    $types[] = 2;
                }

                // сформируем ss_userNotification
                $ss_userNotification = [
                    'templateId' => $eventNotificationGroup->getTemplateId(),
                    'types' => $types,
                    'users' => $users,
                    'createdAt' => StdLib::getMysqlDateTime(),
                    'data' => $notificationData
                ];

                // заполнение данными и отправка
                $GearmanClient->doBackground("{$serviceConfig['gearman']['workerPrefix']}_notification", json_encode($ss_userNotification));
            }
        }
    }
}