<?php

/**
 * Менеджер отсылки документов отдера по eMail
 * Created by PhpStorm.
 * User: v.ikonnikov
 * Date: 19.10.17
 * Time: 16:25
 */
class SendDocumentMgr
{
    /**
     * @var GearmanClient
     */
    protected $gearmanClient;
    protected $serviceConfig;
    protected $subject;
    protected $module;

    public function __construct($module)
    {
        $this->module = $module;
        // создадим гирман клиент
        $this->serviceConfig = Yii::app()->getModule('orderService')->getConfig();
        if (empty($this->serviceConfig['gearman'])) {
            throw new InvalidArgumentException('Gearman не настроен', OrdersErrors::CONFIGURATION_ERROR);
        }

        $this->gearmanClient = new GearmanClient();
        $this->gearmanClient->addServer($this->serviceConfig['gearman']['host'], $this->serviceConfig['gearman']['port']);
    }

    public function run($params)
    {

        $event = EventRepository::getByEventName('SENDDOCUMENT');
        if (is_null($event)) {
            throw new ReportException('Не найдено событие SENDDOCUMENT');
        }
        $orderDoc = OrderDocumentRepository::getOrderDocumentByDocumentId($params['documentId']);
        if (is_null($orderDoc)){
            LogHelper::logExt(get_class($this), __METHOD__, "Не найден документ № {$params['documentId']} из kt_orders_doc для отправки", '', ['$params'=>$params], 'trace', 'system.orderservice.error');
            return false;
        }
        $fileURL = $orderDoc->getFileURL();
        $position = strpos($fileURL, 'storage://');                 // позиция storage://
        $prefix = Yii::getPathOfAlias('webroot') . '/storage/';     // /var/www/html/dev-kmp.travel/storage/
        if($position !== false && $position >= 0){ // замена 'storage://' на  $prefix
            $fullUrl = str_replace('storage://', $prefix, $fileURL);
        }else{ // / замена кривого пути на  $prefix
            $lastCharPos=(strrpos($fileURL, '/') === false) ? 0 : strrpos($fileURL, '/')+1;
            $fullUrl = substr_replace($fileURL, $prefix, 0, $lastCharPos);
        }
        if (!file_exists($fullUrl)){    //  /var/www/html/dev-kmp.travel/storage/ХХХХХ.png
            LogHelper::logExt(get_class($this), __METHOD__, "Проверка пути размещения файла. Документа по указанному пути нет.", '', ['$params'=>$params, 'fullUrl'=>$fullUrl], 'trace', 'system.orderservice.error');
            return false;
        }

        $eventNotificationGroups = NotificationGroupRepository::getNotificationGroups($event, 'sendfile');

        foreach ($eventNotificationGroups as $eventNotificationGroup) {
            // сформируем ss_userNotification
            $ss_userNotification = [
                'templateId' => $eventNotificationGroup->getTemplateId(),
                'types' => [1], // только email
                'users' => [],
                'address' => $params['email'],
                'createdAt' => StdLib::getMysqlDateTime(),
                'data' => [
                    'attachments' => [
                        [
                            'url' => $fullUrl
                        ]
                    ],
                    'event' => $event->toArray(),
                    'subject' => $this->subject
                ]
            ];

            // заполнение данными и отправка
            $this->gearmanClient->doBackground("{$this->serviceConfig['gearman']['workerPrefix']}_notification", json_encode($ss_userNotification));
        }

        return true;
    }

    /**
     * @param mixed $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

}